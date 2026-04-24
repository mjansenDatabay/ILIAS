/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

package de.ilias.services.lucene.settings;

import de.ilias.services.db.DBFactory;
import de.ilias.services.settings.LocalSettings;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.Date;
import java.util.HashMap;

/**
 * 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id$
 */
public class LuceneSettings {
	
	public static final int OPERATOR_AND = 1;
	public static final int OPERATOR_OR = 2;
	
	protected static Logger logger = LogManager.getLogger(LuceneSettings.class);
	private static final HashMap<String, LuceneSettings> instances = new HashMap<String, LuceneSettings>();
	
	
	private int fragmentSize = 30;
	private int numFragments = 3;
	private int defaultOperator = OPERATOR_AND;
	private Date lastIndexTime = new java.util.Date();
	private int prefixWildcard = 0;
	
	/**
	 * Constructor
	 * @throws SQLException 
	 */
	public LuceneSettings() throws SQLException {
		super();
		readSettings();
	}
	
	
	/**
	 * Get singleton instance for a client
	 * @return FieldInfo 
	 * @throws SQLException 
	 */
	public static LuceneSettings getInstance() throws SQLException {
		
		return getInstance(LocalSettings.getClientKey());
	}

	/**
	 * @param clientKey
	 * @return
	 * @throws SQLException 
	 */
	public static LuceneSettings getInstance(String clientKey) throws SQLException {

		if(instances.containsKey(clientKey)) {
			return instances.get(clientKey);
		}
		
		instances.put(clientKey, new LuceneSettings());
		return instances.get(clientKey);
	}

	public boolean refresh() throws SQLException {
		
		readSettings();
		return true;
	}
	

	/**
	 * @return the fragmentSize
	 */
	public int getFragmentSize() {
		return fragmentSize;
	}

	/**
	 * @param fragmentSize the fragmentSize to set
	 */
	public void setFragmentSize(int fragmentSize) {
		this.fragmentSize = fragmentSize;
	}

	/**
	 * @return the numFragments
	 */
	public int getNumFragments() {
		return numFragments;
	}

	/**
	 * @param numFragments the numFragments to set
	 */
	public void setNumFragments(int numFragments) {
		this.numFragments = numFragments;
	}

	/**
	 * @return the defaultOperator
	 */
	public int getDefaultOperator() {
		return defaultOperator;
	}

	/**
	 * @param defaultOperator the defaultOperator to set
	 */
	public void setDefaultOperator(int defaultOperator) {
		this.defaultOperator = defaultOperator;
	}
	
	public boolean isPrefixWildcardQueryEnabled()
	{
		return this.prefixWildcard > 0;
	}
	
	public void enablePrefixWildcardQuery(int stat) {
		
		this.prefixWildcard = stat;
	}
	// databay-patch: begin db-connection
	public static void writeLastIndexTime() throws SQLException {
		final String query = "INSERT INTO settings (value,module,keyword) VALUES (?,?,?) ";
		int attempts = 0;
		while(true) {
			attempts++;
			Statement sta = null;
			try {
				sta = DBFactory.factory().createStatement();
				sta.executeUpdate("DELETE FROM settings " +
						"WHERE module = 'common' AND keyword = 'lucene_last_index_time'");
				try {
					sta.close();
				}
				catch (SQLException e) {
					logger.warn(e);
				}
				finally {
					sta = null;
				}

				PreparedStatement pst = DBFactory.getPreparedStatement(query);
				pst.setString(1, String.valueOf(new java.util.Date().getTime() / 1000));
				pst.setString(2, "common");
				pst.setString(3, "lucene_last_index_time");
				pst.executeUpdate();
				DBFactory.closePreparedStatement(query);
				return;
			}
			catch (SQLException e) {
				try {
					DBFactory.closePreparedStatement(query);
				}
				catch (Throwable t) {
					// ignore
				}
				try {
					if (sta != null) {
						sta.close();
					}
				}
				catch (Throwable t) {
					// ignore
				}

				if (attempts < 2 && isConnectionException(e)) {
					logger.warn("DB connection lost while writing lucene_last_index_time; retrying once", e);
					DBFactory.init();
					continue;
				}
				throw e;
			}
		}
	}

	private static boolean isConnectionException(SQLException e) {
		if (e instanceof java.sql.SQLNonTransientConnectionException) {
			return true;
		}
		if (e instanceof java.sql.SQLRecoverableException) {
			return true;
		}
		String state = e.getSQLState();
		if (state != null && state.startsWith("08")) {
			return true;
		}
		String msg = e.getMessage();
		if (msg != null) {
			String m = msg.toLowerCase();
			if (m.contains("socket") || m.contains("connection is closed") || m.contains("communications link failure")) {
				return true;
			}
		}
		Throwable c = e.getCause();
		if (c instanceof SQLException) {
			return isConnectionException((SQLException) c);
		}
		return false;
	}
	// databay-patch: end db-connection

	/**
	 * @param date
	 */
	public void setLastIndexTime(Date date) {

		lastIndexTime = date;
	}
	
	/**
	 * get datetime of last index
	 * @return
	 */
	public Date getLastIndexTime() {
		return lastIndexTime;
	}

	// databay-patch: begin db-connection
	/**
	 * @throws SQLException 
	 * 
	 */
	private void readSettings() throws SQLException {
		int attempts = 0;
		while(true) {
			attempts++;
			Statement sta = null;
			ResultSet res = null;
			try {
				sta = DBFactory.factory().createStatement();

				res = sta.executeQuery("SELECT value FROM settings WHERE module = 'common' " +
						"AND keyword = 'lucene_default_operator'");
				while(res.next()) {
					setDefaultOperator(Integer.parseInt(res.getString("value")));
					logger.info("Default Operator is: " + getDefaultOperator());
				}
				try { res.close(); } catch (SQLException e) { logger.warn(e); }
				res = null;

				res = sta.executeQuery("SELECT value FROM settings WHERE module = 'common' " +
						"AND keyword = 'lucene_prefix_wildcard'");
				while(res.next()) {
					try {
						if(res.getString("value").length() > 0) {
							this.enablePrefixWildcardQuery(Integer.parseInt(res.getString("value")));
						}
					}
					catch(NumberFormatException e) {
						logger.warn("Read invalid setting: " + e.getMessage());
						this.enablePrefixWildcardQuery(0);
					}
					logger.info("Prefix wildcard queries enabled: " + (this.isPrefixWildcardQueryEnabled() ? "yes" : "no"));
				}
				try { res.close(); } catch (SQLException e) { logger.warn(e); }
				res = null;

				res = sta.executeQuery("SELECT value FROM settings WHERE module = 'common' " +
						"AND keyword = 'lucene_fragment_size'");
				while(res.next()) {
					setFragmentSize(Integer.parseInt(res.getString("value")));
					logger.info("Fragment size is: " + getFragmentSize());
				}
				try { res.close(); } catch (SQLException e) { logger.warn(e); }
				res = null;

				res = sta.executeQuery("SELECT value FROM settings WHERE module = 'common' " +
						"AND keyword = 'lucene_fragment_count'");
				while(res.next()) {
					setNumFragments(Integer.parseInt(res.getString("value")));
					logger.info("Number of fragments is: " + getNumFragments());
				}
				try { res.close(); } catch (SQLException e) { logger.warn(e); }
				res = null;

				res = sta.executeQuery("SELECT value FROM settings WHERE module = 'common' " +
						"AND keyword = 'lucene_last_index_time'");
				while(res.next()) {
					logger.info("Date:" + res.getString("value"));
					Date date = new Date((long) Integer.parseInt(res.getString("value")) * 1000);
					logger.info(date);
					setLastIndexTime(new Date((long) Integer.parseInt(res.getString("value")) * 1000));
				}
				return;
			}
			catch (SQLException e) {
				if (attempts < 2 && isConnectionException(e)) {
					logger.warn("DB connection lost while reading lucene settings; retrying once", e);
					DBFactory.init();
					continue;
				}
				throw e;
			}
			finally {
				try {
					if (res != null) {
						res.close();
					}
				}
				catch (SQLException e) {
					logger.warn(e);
				}
				try {
					if (sta != null) {
						sta.close();
					}
				}
				catch (SQLException e) {
					logger.warn(e);
				}
			}
		}
		
	}
	// databay-patch: end db-connection

}
