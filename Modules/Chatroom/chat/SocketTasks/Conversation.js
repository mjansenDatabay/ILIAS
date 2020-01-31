var Container = require('../AppContainer');
var UUID	  = require('node-uuid');
var Conversation = require('../Model/Conversation');

	/**
 * @param {Array} participants
 */
module.exports = function(participants) {
	Container.getLogger().info('[Onscreen Task]: (Conversation) A conversation has been requested in namespace %s!', this.nsp.name);
	Container.getLogger().debug('[Onscreen Task]: (Conversation) Participants %s!', JSON.stringify(participants));
	var namespace = Container.getNamespace(this.nsp.name);
	var conversations = namespace.getConversations();
	var conversation = conversations.getForParticipants(participants);
	var socket = this;

	if (conversation == null) {
		Container.getLogger().info("[Onscreen Task]: (Conversation) Couldn't find a conversation in namespace %s. Creating a new one!", namespace.getName());
		conversation = new Conversation(UUID.v4());
		conversations.add(conversation);

		namespace.getDatabase().persistConversation(conversation);
	}

	if (participants.length > 2) {
		Container.getLogger().info("[Onscreen Task]: (Conversation) Convert the conversation to a group conversation in namespace %s", namespace.getName());
		conversation.setIsGroup(true);
	}

	for (var key in participants) {
		var participant = namespace.getSubscriberWithOfflines(participants[key].id, participants[key].name);
		conversation.addParticipant(participant);
		participant.join(conversation.id);
	}

	namespace.getDatabase().updateConversation(conversation);

	function onLastConversationMessageResult(row) {
		row.userId = row.user_id;
		row.conversationId = row.conversation_id;
		conversation.setLatestMessage(row);
	}

	function onLastConversationMessageEnd() {
		socket.participant.emit('conversation-init', conversation.json());
	}

	//namespace.getDatabase().getLatestMessage(conversation, onLastConversationMessageResult, onLastConversationMessageEnd);

	socket.participant.emit('conversation-init', conversation.json());
	Container.getLogger().info("[Onscreen Task]: (Conversation) Done for namespace %s", namespace.getName());
};
