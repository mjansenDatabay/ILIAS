var async = require('async');
var Container = require('../AppContainer');
var Conversation = require('../Model/Conversation');
var Participant = require('../Model/ConversationParticipant');

/**
 * @param {Namespace} namespace
 * @param {Function} callback
 */
module.exports = function PreloadConversations(namespace, callback) {
	Container.getLogger().info('[Boot process]: Load conversations for namespace %s started!', namespace.getName());

	function onConversationResult(row, callback) {
		var participants = JSON.parse(row.participants);

		var conversation = new Conversation(row.id);
		conversation.setIsGroup(row.is_group);

		for (var index in participants) {
			if (participants.hasOwnProperty(index)) {
				var participant = namespace.getSubscriberWithOfflines(participants[index].id, participants[index].name);
				participant.setOnline(false);
				participant.join(conversation.getId());

				conversation.addParticipant(participant);
				namespace.addSubscriber(participant);
			}
		}
		namespace.getConversations().add(conversation);
	}

	function onConversationsPreloaded() {
		var conversations = namespace.getConversations().all();

		async.each(conversations, function (conversation, nextConversation) {
			async.parallel({
				loadLatestMessage: function (next) {
					namespace.getDatabase().getLatestMessage(conversation, function onLatestMessageResult(row) {
						row.userId = row.user_id;
						row.conversationId = row.conversation_id;
						conversation.setLatestMessage(row);
					}, function onLatestMessageEnd() {
						next(null);
					});
				},
				loadParticipantState: function (next) {
					namespace.getDatabase().getConversationState(conversation.getId(), function onParticipantState(row) {
						var participantState = conversation.getActivityForParticipant(row.user_id);
						participantState.trackActivity(row.timestamp);
						participantState.setConversationClosed(row.is_closed);
					}, function onParticipateStateEnd() {
						next(null);
					});
				},
				countUnreadMessages: function (next) {
					var participants = conversation.getParticipants();
					async.each(participants, function (participant, nextParticipant) {
						namespace.getDatabase().countUnreadMessages(conversation.getId(), participant.getId(), function onUnreadMessages(row) {
							var participantState = conversation.getActivityForParticipant(participant.getId());
							participantState.setUnreadMessages(row.numMessages);
						}, function onUnreadMessagesEnd() {
							nextParticipant(null);
						});
					}, function () {
						next();
					});
				}
			}, function () {
				nextConversation(null);
			});
		}, function (err) {
			if (err) {
				throw err;
			}

			Container.getLogger().info('[Boot process]: Load conversations for namespace %s done!', namespace.getName());

			callback(null, namespace);
		});
	}

	namespace.getDatabase().loadConversations(onConversationResult, onConversationsPreloaded);
};
