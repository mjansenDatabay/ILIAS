var Container = require('../AppContainer');
var Participant = require('../Model/ConversationParticipant');
var ListConversations = require('./ListConversations');

module.exports = function (id, name) {
	var namespace = Container.getNamespace(this.nsp.name);

	Container.getLogger().info('[Onscreen Task]: (ConversationLogin) Conversation login by %s in namespace %s started!', name, namespace.getName());

	var participant = namespace.getSubscriber(id);

	if (participant == null) {
		participant = new Participant(id, name);
		namespace.addSubscriber(participant);
	}
	participant.setName(name);
	participant.addSocket(this);
	participant.setOnline(true);

	Container.getLogger().debug('[Onscreen Task]: (ConversationLogin) Participant %s', JSON.stringify(participant));

	this.participant = participant;
	this.emit('login', participant.json());

	ListConversations.call(this);
	Container.getLogger().info('[Onscreen Task]: (ConversationLogin) Done in namespace %s!', namespace.getName());
};
