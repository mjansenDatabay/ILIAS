module.exports = function MessageQueue() {

	/**
	 * @type {Array.<Message>}
	 */
	var _elements = [];

	/**
	 * Add a message to the queue.
	 *
	 * @param {Message} element
	 */
	this.push = function push(element) {
		_elements.push(element);
	}

	/**
	 * Extract all messages from the queue.
	 *
	 * @returns {Message[]}
	 */
	this.popAll = function () {
		// create a copy of the current _elements array
		var elements = _elements.slice();
		// calculate the length of the extracted array
		var length = elements.length;
		// Remove all extracted elements from the _elements array
		_elements = _elements.slice(length)
		// Return all extracted elements
		return elements;
	}

	/**
	 * Get the size of the queue.
	 *
	 * @returns {number}
	 */
	this.size = function size() {
		return _elements.length;
	}

	/**
	 * Return true if the queue is empty.
	 *
	 * @returns {boolean}
	 */
	this.isEmpty = function isEmpty() {
		return this.size() === 0;
	}
}
