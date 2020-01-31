var Database = require('../Persistence/Database');
var Container = require('../AppContainer');


/**
 * @param {Namespace} namespace
 * @param {JSON} config
 * @param {Function} callback
 */
module.exports = function SetupDatabase(namespace, config, callback) {
	Container.getLogger().info('Setup database for namespace %s', namespace.getName());
	Container.getLogger().log('debug', 'Setup database with config %s', JSON.stringify(config));

	var database = new Database(config);
	namespace.setDatabase(database);
	database.connect();

	callback(null, namespace);
	/*database.connect(function onDatabaseConnect(err, connection) {
		if(err) {
			throw err;
		}

		Container.getLogger().info('Database for %s connected!', namespace.getName());
		connection.release();

		callback(null, namespace);
	});*/
};
