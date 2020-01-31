var Container = require('../AppContainer');
var Conversation = require('../Model/Conversation');
var UUID = require('node-uuid');

module.exports = function(conversationId, userId, name) {
	if (conversationId !== null && userId !== null && name !== null) {
		var namespace = Container.getNamespace(this.nsp.name);
		var conversation = namespace.getConversations().getById(conversationId);

		Container.getLogger().info("[Onscreen Task]: (ConversationAddUser) Add new user to conversation %s in namespace %s started!", conversationId, namespace.getName());

		if (conversation.isParticipant(this.participant)) {
			var newParticipant = namespace.getSubscriberWithOfflines(userId, name);
			var participants   = conversation.getParticipants();

			if (!conversation.isGroup()) {
				conversation = new Conversation(UUID.v4());
				conversation.setIsGroup(true);

				namespace.getConversations().add(conversation);
				namespace.getDatabase().persistConversation(conversation);

				for (var key in participants) {
					if (participants.hasOwnProperty(key)) {
						conversation.addParticipant(participants[key]);
					}
				}

				Container.getLogger().info("[Onscreen Task]: (ConversationAddUser) Convert conversation %s to group in namespace %s started!", conversationId, namespace.getName());
			}

			if (conversation.isParticipant(newParticipant)) {
				Container.getLogger().info('[Onscreen Task]: (ConversationAddUser) Participant %s is already subscribed to conversation %s.', newParticipant.getName(), conversation.getId());
				return;
			}

			conversation.addParticipant(newParticipant);
			newParticipant.join(conversation.id);

			participants = conversation.getParticipants();
			for (var key in participants) {
				if (participants.hasOwnProperty(key)){
					conversation.trackActivity(participants, 0)
					namespace.getDatabase().trackActivity(conversation.getId(), participants[key].getId(), 0);
				}
			}

			Container.getLogger().info('[Onscreen Task]: (ConversationAddUser) New Participant %s for group conversation %s', newParticipant.getName(), newParticipant.getId());

			namespace.getDatabase().updateConversation(conversation);

			this.participant.emit('conversation', conversation.json());
			this.emit('addUser', conversation.json());
		}

		Container.getLogger().info("[Onscreen Task]: (ConversationAddUser) Done in namespace %s!", conversationId, namespace.getName());
	}
};