var Namespace = require('../Model/Namespace');
var MessageQueue = require('../Model/MessageQueue');
var Container = require('../AppContainer');

module.exports = function createNamespace(name) {
	var namespace = new Namespace(name, new MessageQueue());

	Container.addNamespace(namespace);

	return namespace;
};