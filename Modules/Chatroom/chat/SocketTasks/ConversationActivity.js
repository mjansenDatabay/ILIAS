var Container = require('../AppContainer');
var Conversation = require('../Model/Conversation');

/**
 * @param {string} conversationId
 * @param {number} userId
 * @param {number} timestamp
 */
module.exports = function(conversationId, userId, timestamp) {
	if(conversationId !== null && userId !== null && timestamp !== null) {
		var namespace = Container.getNamespace(this.nsp.name);
		var conversation = namespace.getConversations().getById(conversationId);

		Container.getLogger().info("[Onscreen Task]: (ConversationActivity) Received new Activity in namespace %s", namespace.getName());

		if (conversation !== null && conversation.isParticipant(this.participant)) {
			conversation.trackActivity(this.participant, timestamp);

			namespace.getDatabase().trackActivity(conversationId, userId, timestamp);
			Container.getLogger().info('[Onscreen Task]: (ConversationActivity) Track Activity for user %s in %s: %s', userId, conversationId, timestamp);
		}

		Container.getLogger().info("[Onscreen Task]: (ConversationActivity) Done in namespace %s", namespace.getName());
	}
};