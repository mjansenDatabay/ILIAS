var ReadCommandArguments = require('./ReadCommandArguments');
var ReadServerConfig = require('./ReadServerConfig');
var ReadClientConfigs = require('./ReadClientConfigs');
var SetupEnvironment = require('./SetupEnvironment');
var SetupExpressApi = require('./SetupExpressApi');
var SetupNamespaces = require('./SetupNamespaces');
var SetupIM = require('./SetupIM');
var SetupExitHandler = require('./SetupExitHandler');
var SetupServer = require('./SetupServer');
var SetupClearMessagesProcess = require('./SetupClearMessagesProcess');
var SetupPersistenceProcess = require('./SetupPersistenceProcess');
var UserSettingsProcess = require('./UserSettingsProcess');
var Container = require('../AppContainer');
var async = require('async');

process.setMaxListeners(0);

var Bootstrap = function Bootstrap() {
	this.boot = function () {
		function onBootCompleted(err, result) {
			Container.getServer().listen(Container.getServerConfig().port, Container.getServerConfig().address);
			Container.getLogger().info("[Boot process]: The Server is Ready to use! Listening on: %s://%s:%s", Container.getServerConfig().protocol, Container.getServerConfig().address, Container.getServerConfig().port);
		}

		async.auto({
			readCommandArguments: ReadCommandArguments,
			setupExpressApi: SetupExpressApi,
			readServerConfig: ['readCommandArguments', ReadServerConfig],
			readClientConfigs: ['readCommandArguments', ReadClientConfigs],
			setupEnvironment: ['readCommandArguments', 'readServerConfig', SetupEnvironment],
			setupNamespaces: ['readClientConfigs', SetupNamespaces],
			setupIM: ['setupNamespaces', SetupIM],
			setupExitHandler: ['setupNamespaces', SetupExitHandler],
			setupServer: ['setupNamespaces', 'setupIM', SetupServer],
			setupPersistenceProcess: ['setupIM', SetupPersistenceProcess],
			setupClearProcess: ['setupServer', SetupClearMessagesProcess],
			setupUserSettingsProcess: ['setupServer', UserSettingsProcess]
		}, onBootCompleted);
	};
};

module.exports = new Bootstrap();
