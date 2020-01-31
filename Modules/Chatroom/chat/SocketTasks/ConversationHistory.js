var Container = require('../AppContainer');

module.exports = function(conversationId, oldestMessageTimestamp, reverseSorting) {
	if(conversationId !== null)
	{
		Container.getLogger().info('[Onscreen Task]: (ConversationHistory) Export history for conversation %s in namespace %s started!', conversationId, this.nsp.name);

		var namespace = Container.getNamespace(this.nsp.name);
		var conversation = namespace.getConversations().getById(conversationId);
		var history = [];
		var oldestTimestamp = oldestMessageTimestamp;
		var socket = this;

		if(conversation.isParticipant(this.participant))
		{
			function onConversationResult(row){
				if(oldestTimestamp === null || oldestTimestamp > row.timestamp) {
					oldestTimestamp = row.timestamp;
				}
				row.userId = row.user_id;
				row.conversationId = row.conversation_id;
				history.push(row);
			}

			function emitConversationHistory(err){
				if(err) {
					throw err;
				}

				var json = conversation.json();
				json.messages = (reverseSorting ? history.reverse() : history);
				json.reverseSorting = reverseSorting;
				json.oldestMessageTimestamp = oldestTimestamp;

				socket.participant.emit('history', json);

				Container.getLogger().info('[Onscreen Task]: (ConversationHistory) Done in namespace %s',
					conversationId,
					namespace.getName(),
					oldestMessageTimestamp
				);
			}

			Container.getLogger().info('[Onscreen Task]: (ConversationHistory) Requested History for %s in namespace %s since %s',
				conversationId,
				namespace.getName(),
				oldestMessageTimestamp
			);

			namespace.getDatabase().loadConversationHistory(
				conversation.getId(),
				oldestMessageTimestamp,
				onConversationResult,
				emitConversationHistory
			);
		}
	}

};