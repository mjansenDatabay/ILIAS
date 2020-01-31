var CONST = require('../Constants');
var Container = require('../AppContainer');
var Handler = require('../Handler/FileHandler');

/**
 * @param {Function} callback
 */
module.exports = function ReadServerConfig(result, callback) {
	var config = Handler.read(Container.getArgument(CONST.SERVER_CONFIG_INDEX));

	if (config.log === undefined || config.log.trim() === "") {
		config.log = 'chat.log';
	}
	if (config.error_log === undefined || config.error_log.trim() === "") {
		config.error_log = 'chatError.log';
	}
	if (config.log_level === undefined || config.log_level.trim() === "") {
		config.log_level = 'info';
	}

	Container.setServerConfig(config);

	callback(null);
};