var Container = require('../AppContainer');
var constants = require('constants');
var SocketIO = require('socket.io');
var async = require('async');
var SocketHandler = require('../Handler/SocketHandler');
var IMSocketHandler = require('../Handler/IMSocketHandler');
var FileHandler = require('../Handler/FileHandler');
var dns = require('dns');

module.exports = function SetupServer(result, callback) {
	Container.getLogger().info('[Boot process]: Setup server listener started!');

	var serverConfig = Container.getServerConfig();
	var io;

	Container.getLogger().log('debug', '[Boot process]: Config %s', JSON.stringify(serverConfig));

	function handleSocket(namespace, next) {
		Container.getLogger().info("[Boot process]: Setup socket handler for namespace %s", namespace.getName());

		namespace.setIO(io.of(namespace.getName()));

		var handler = SocketHandler;

		if (namespace.isIM()) {
			handler = IMSocketHandler;
		}

		Container.getLogger().log('debug', '[Boot process]: Setup %s handler for namespace %s', handler.name, namespace.getName());

		namespace.getIO().on('connect', handler);

		next();
	}

	function onSocketHandled(err) {
		if (err) {
			throw err;
		}

		Container.getLogger().info('[Boot process]: Setup server listener done!');

		callback();
	}

	function bootServer(serverConfig) {

		var options = _generateOptions(serverConfig);
		var protocol = require(serverConfig.protocol);
		var server = null;
		var path = '/socket.io';

		if (serverConfig.hasOwnProperty('sub_directory')) {
			path = serverConfig.sub_directory + path;
		}

		if (serverConfig.protocol === 'https') {
			server = protocol.createServer(options, Container.getApi());
		} else {
			server = protocol.createServer(Container.getApi());
		}

		Container.getLogger().log('debug', "[Boot process]: Start listener with server %s and path %s", JSON.stringify(server), JSON.stringify(path));

		io = SocketIO(server, {path: path});

		Container.setServer(server);

		async.eachSeries(Container.getNamespaces(), handleSocket, onSocketHandled);
	}

	function onHostnameResolved(err, resolvedAddress, family) {
		Container.getLogger().info("[Boot process]: Resolve DNS for: %s => IP: %s , Family: %s", serverConfig.address, resolvedAddress, family);
		serverConfig.address = resolvedAddress;
		bootServer(serverConfig);
	}

	dns.lookup(serverConfig.address, onHostnameResolved);
};


function _generateOptions(config) {
	var options = {
		host: Container.getServerConfig().address
	};

	if (config.protocol === 'https') {
		options.cert = FileHandler.readPlain(config.cert);
		options.key = FileHandler.readPlain(config.key);
		options.dhparam = FileHandler.readPlain(config.dhparam);
		options.ciphers = [
			"ECDHE-RSA-AES256-SHA384",
			"DHE-RSA-AES256-SHA384",
			"ECDHE-RSA-AES256-SHA256",
			"DHE-RSA-AES256-SHA256",
			"ECDHE-RSA-AES128-SHA256",
			"DHE-RSA-AES128-SHA256",
			"HIGH",
			"!aNULL",
			"!eNULL",
			"!EXPORT",
			"!DES",
			"!3DES",
			"!RC4",
			"!MD5",
			"!PSK",
			"!SRP",
			"!CAMELLIA"
		].join(':');
		//options.honorCipherOrder = true;
		options.secureProtocol = 'SSLv23_method';
		options.secureOptions = constants.SSL_OP_NO_SSLv3;
	}

	return options;
}