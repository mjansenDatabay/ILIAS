var Container = require('../AppContainer');

/**
 * @param {string} conversationId
 * @param {number} userId
 * @param {number} timestamp
 */
module.exports = function (conversationId, userId) {
	if (conversationId !== null && userId !== null) {
		var namespace = Container.getNamespace(this.nsp.name);
		var conversation = namespace.getConversations().getById(conversationId);

		Container.getLogger().info("[Onscreen Task]: (ConversationClose) Close conversation %s in namespace %s started!", conversationId, namespace.getName());

		if (conversation.isParticipant(this.participant)) {
			var conversationState = conversation.getActivityForParticipant(this.participant.getId());
			conversationState.setConversationClosed(true);
			Container.getLogger().info('[Onscreen Task]: (ConversationClose) Close Conversation %s for participant %s', conversationId, userId);
		}

		Container.getLogger().info("[Onscreen Task]: (ConversationClose) Done %s in namespace!", namespace.getName());
	}
};