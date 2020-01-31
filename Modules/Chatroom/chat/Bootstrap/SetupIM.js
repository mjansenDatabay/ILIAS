var Container = require('../AppContainer');
var Handler = require('../Handler/NamespaceHandler');
var async = require('async');
var PreloadConversations = require('./PreloadConversations');

module.exports = function SetupIM(result, callback) {
	Container.getLogger().info('[Boot process]: Setup onscreen namespaces started!');

	function setupIMNamespace(namespace, nextLoop) {
		Container.getLogger().log('debug','[Boot process]: Namespace %s', namespace.getName());

		function createIMNamespace(callback) {
			Container.getLogger().info('[Boot process]: Create onscreen namespace with name %s-im', namespace.getName());

			var namespaceIM = Handler.createNamespace(namespace.getName() + '-im');
			namespaceIM.setIsIM(true);
			namespaceIM.setDatabase(namespace.getDatabase());

			callback(null, namespaceIM);
		}

		function onIMNamespaceSetupFinished(err, result) {
			if(err) {
				throw err;
			}

			Container.getLogger().info('[Boot process]: Created namespace with name %s!', result.getName());

			nextLoop();
		}

		async.waterfall(
			[
				createIMNamespace,
				PreloadConversations
			],
			onIMNamespaceSetupFinished
		);
	}

	function onIMSetupFinished(err) {
		if(err) {
			throw err;
		}

		Container.getLogger().info('[Boot process]: Setup onscreen namespaces done!');

		callback();
	}

	async.eachSeries(Container.getNamespaces(), setupIMNamespace, onIMSetupFinished);
};
