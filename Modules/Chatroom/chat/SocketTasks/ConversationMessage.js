var Container  = require('../AppContainer');
var HTMLEscape = require('../Helper/HTMLEscape');
var UUID = require('node-uuid');

module.exports = function(conversationId, userId, message) {
	function shouldPersistMessage(conversation) {
		var doStoreMessage = conversation.isGroup();

		if (!conversation.isGroup()) {
			var participants = conversation.getParticipants();

			for (var index in participants) {
				if (participants.hasOwnProperty(index) && participants[index].getAcceptsMessages()) {
					doStoreMessage = true;
					break;
				}
			}
		}

		return doStoreMessage;
	}

	if (conversationId !== null && userId !== null && message !== null) {
		Container.getLogger().info('[Onscreen Task]: (ConversationMessage) New message for conversation %s in namespace %s!', conversationId, this.nsp.name);

		var namespace = Container.getNamespace(this.nsp.name);
		var conversation = namespace.getConversations().getById(conversationId);
		var participant = namespace.getSubscriber(userId);

		if(conversation.isParticipant(participant))
		{
			var messageObj = {
				conversationId: conversationId,
				userId: userId,
				message: HTMLEscape.escape(message),
				timestamp: (new Date).getTime(),
				id: UUID.v4()
			};

			if (participant.getAcceptsMessages()) {
				var doStoreMessage = shouldPersistMessage(conversation);

				if (doStoreMessage) {
					Container.getLogger().debug('[Onscreen Task]: (ConversationMessage) Add message to queue for conversation %s in namespace %s', userId, conversationId, namespace.getName());
					namespace.getMessageQueue().push(messageObj);
				}

				conversation.emit('conversation', conversation.json());
				conversation.setLatestMessage(messageObj);

				var ignoredParticipants = conversation.send(messageObj);

				if (Object.keys(ignoredParticipants).length > 0) {
					messageObj["ignoredParticipants"] = ignoredParticipants;
					participant.emit("participantsSuppressedMessages", messageObj);
				}

				Container.getLogger().info('[Onscreen Task]: (ConversationMessage) Message by "%s" for conversation %s in namespace %s', userId, conversationId, namespace.getName());
			} else {
				participant.emit("senderSuppressesMessages", messageObj);
				Container.getLogger().info('[Onscreen Task]: (ConversationMessage) Message by "%s" for conversation %s in namespace %s not delivered, user does not want to receive messages', userId, conversationId, namespace.getName());
			}
		}

		Container.getLogger().info('[Onscreen Task]: (ConversationMessage) Done for conversation %s in namespace %s', conversationId, namespace.getName());
	}
};
