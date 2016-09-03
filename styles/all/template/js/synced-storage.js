/*!
 *
 * Synced Storage v1.0.1
 *
 * https://github.com/kasimi/JS-Synced-Storage
 * Copyright 2016 kasimi
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */
var syncedStorage = function(options) {

	"use strict";

	var mergeFlatDefensive = function(obj, mergingObj) {
		for (var key in mergingObj) {
			if (mergingObj.hasOwnProperty(key) && !obj.hasOwnProperty(key)) {
				obj[key] = mergingObj[key];
			}
		}
	};

	function StorageWrapper(storage, prefix, eventKeys) {
		this.prefix = prefix;
		this.storage = this.getStorage(storage) || false;

		// Register event listener
		if (Object.keys(eventKeys || {}).length > 0) {
			window.addEventListener('storage', function(e) {
				var key = e.key.substr(prefix.length);
				if (eventKeys.hasOwnProperty(key)) {
					eventKeys[key](e);
				}
			});
		}
	}

	StorageWrapper.prototype.getStorage = function(type) {
		try {
			var storage = window[type];
			var x = this.prefix;
			storage.setItem(x, x);
			storage.removeItem(x);
			return storage;
		} catch (e) {
			return false;
		}
	};

	StorageWrapper.prototype.set = function(key, value) {
		this.storage && this.storage.setItem(this.prefix + key, value);
	};

	StorageWrapper.prototype.get = function(key) {
		return this.storage && this.storage.getItem(this.prefix + key);
	};

	mergeFlatDefensive(options, {
		storage				: 'localStorage',
		storageKeyPrefix	: 'synced_storage_',
		sessionLength		: false,
		cachedDataTTL		: options.updateInterval
	});

	var lastStorageEventTime = 0;
	var sessionLength = options.sessionLength;
	var storage = new StorageWrapper(options.storage, options.storageKeyPrefix, {
		time: function(e) {
			lastStorageEventTime = e.newValue;
		},
		content: function() {
			options.processData(readFromStorage());
		}
	});

	var writeToStorage = function(data) {
		var storageContent = JSON.stringify({
			// We add a "unique" value here to make sure this object is different from the one
			// in the last call to writeToStorage() to ensure the 'storage' event is triggered.
			time: Date.now(),
			data: data
		});
		storage.set('content', storageContent);
	};

	var readFromStorage = function() {
		var storageContent = storage.get('content');
		return JSON.parse(storageContent).data;
	};

	var updateData = function(done) {
		if (lastStorageEventTime > Date.now() - options.updateInterval) {
			done();
		} else {
			storage.set('time', Date.now());
			options.getData(function(data) {
				writeToStorage(data);
				options.processData(data);
				done();
			});
		}
	};

	var scheduleUpdate = function() {
		setTimeout(function() {
			updateData(function() {
				if (sessionLength === false || (sessionLength -= options.updateInterval) > 0) {
					scheduleUpdate();
				}
			});
		}, options.updateInterval);
	};

	scheduleUpdate();
};
