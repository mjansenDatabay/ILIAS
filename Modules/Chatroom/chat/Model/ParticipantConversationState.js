

module.exports = function ParticipantConversationState(id) {

	/**
	 * @type {number}
	 * @private
	 */
	var _id = id;

	/**
	 * @type {number}
	 * @private
	 */
	var _lastActivity = 0

	/**
	 * @type {number}
	 * @private
	 */
	var _unreadMessages = 0;

	/**
	 * @type {boolean}
	 * @private
	 */
	var _conversationClosed = false;

	/**
	 *
	 * @type {number}
	 * @private
	 */
	var _lastUpdated = 0;

	/**
	 * Get the participant id.
	 *
	 * @returns {number}
	 */
	this.getId = function getId() {
		return _id;
	}

	/**
	 * Track the last activity
	 *
	 * @param {number} timestamp
	 */
	this.trackActivity = function(timestamp) {
		this.resetUnreadMessages();
		_lastActivity = timestamp;
	}

	/**
	 * Get the last activity timestamp
	 *
	 * @returns {number}
	 */
	this.getLastActivity = function getLastActivity() {
		return _lastActivity;
	}

	/**
	 * Increase the number of unread messages by one.
	 */
	this.increaseUnreadMessages = function increaseUnreadMessages() {
		_unreadMessages++;
	}

	/**
	 * Reset the number of unread messages to zero.
	 */
	this.resetUnreadMessages = function resetUnreadMessages() {
		_unreadMessages = 0;
	}

	/**
	 * Get the number of unread messages.
	 *
	 * @returns {number}
	 */
	this.getNumUnreadMessages = function getNumUnreadMessages() {
		return _unreadMessages;
	}

	/**
	 * Set the number of unread messages
	 *
	 * @param {number} num
	 */
	this.setUnreadMessages = function setUnreadMessages(num) {
		_unreadMessages = num;
	}

	/**
	 * Return true if the conversation has been closed by the participant
	 *
	 * @returns {boolean}
	 */
	this.hasClosedConversation = function hasClosedConversation(){
		return _conversationClosed;
	}

	/**
	 * Close the conversation.
	 *
	 * @param {boolean} isClosed
	 */
	this.setConversationClosed = function setConversationClosed(isClosed) {
		_conversationClosed = isClosed;
	}

	/**
	 * Set the timestamp this object has been updated the last time
	 *
	 * @param {number} timestamp
	 */
	this.lastUpdated = function lastUpdated(timestamp) {
		_lastUpdated = timestamp;
	}

	/**
	 * Check if the last update deffers from the last activity
	 *
	 * @returns {boolean}
	 */
	this.isUpdated = function isUpdated() {
		return _lastActivity !== _lastUpdated;
	}

}
