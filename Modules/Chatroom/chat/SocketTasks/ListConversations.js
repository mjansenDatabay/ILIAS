var Container = require('../AppContainer');
var async = require('async');

module.exports = function () {

	var namespace = Container.getNamespace(this.nsp.name);
	var conversations = this.participant.getConversations();
	var socket = this;

	Container.getLogger().info('[Onscreen Task]: (ListConversations) List conversations in namespace %s', namespace.getName());

	async.eachSeries(conversations, function onConversation(conversation, nextLoop) {
		var conversationState = conversation.getActivityForParticipant(socket.participant.getId());

		if (!conversationState.hasClosedConversation() || (conversationState.getNumUnreadMessages() > 0 && !conversation.isGroup())) {
			var jsonConversation = conversation.json();
			jsonConversation.numNewMessages = conversationState.getNumUnreadMessages();
			socket.participant.emit('conversation', jsonConversation);
		}

		nextLoop();
	}, function (err) {
		if (err) {
			throw err;
		}

		Container.getLogger().info('[Onscreen Task]: (ListConversations) Done in namespace %s', namespace.getName());
	});
};
