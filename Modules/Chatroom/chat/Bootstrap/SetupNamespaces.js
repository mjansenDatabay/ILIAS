var Container = require('../AppContainer');
var Handler = require('../Handler/NamespaceHandler');
var SetupDatabase = require('./SetupDatabase');
var PreloadData = require('./PreloadData');
var async = require('async');

module.exports = function SetupNamespaces(result, callback) {
	Container.getLogger().info('[Boot process]: Setup namespaces started!')

	var clientConfigs = Container.getClientConfigs();

	function setupNamespace(config, nextLoop) {
		Container.getLogger().log('debug', "[Boot process]: Config %s", JSON.stringify(config));

		function createNamespace(callback) {
			Container.getLogger().info('[Boot process]: Create namespace with name %s!', config.name);

			var namespace = Handler.createNamespace(config.name);
			callback(null, namespace, config);
		}

		function onNamespaceSetupFinished(err, result) {
			if (err) {
				throw err;
			}

			Container.getLogger().info('[Boot process]: Created namespace with name %s!', config.name);
			nextLoop();
		}

		async.waterfall(
			[
				createNamespace,
				SetupDatabase,
				PreloadData
			],
			onNamespaceSetupFinished
		);
	}

	function onNamespacesSetupFinished(err) {
		if (err) {
			throw err;
		}

		Container.getLogger().info('[Boot process]: Setup namespaces done!');

		callback();
	}

	async.eachSeries(clientConfigs, setupNamespace, onNamespacesSetupFinished);
};
