var Container = require('../AppContainer');

module.exports = function IMSocketHandler(socket) {
	Container.getLogger().info('[Onscreen Task]: (SocketHandler) New connection by socket: %s', socket.id);

	socket.on('login', _getTask('ConversationLogin'));
	socket.on('conversations', _getTask('ListConversations'));
	socket.on('conversation', _getTask('Conversation'));
	socket.on('addUser', _getTask('ConversationAddUser'));
	socket.on('removeUser', _getTask('ConversationRemoveUser'));
	socket.on('message', _getTask('ConversationMessage'));
	socket.on('history', _getTask('ConversationHistory'));
	socket.on('activity', _getTask('ConversationActivity'));
	socket.on('closeConversation', _getTask('ConversationClose'));
};

function _getTask(name) {
	return require('../SocketTasks/'+ name);
}