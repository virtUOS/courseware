import jQuery from 'jquery'
import queryString from 'query-string'
import _ from 'underscore'
import URL from 'url-parse'
import Config from './courseware-config'

function ajax(options) {
  return new Promise(function (resolve, reject) {
    jQuery.ajax(options).done(resolve).fail(reject);
  });
}

export default {

  // URL generation

  block_url(block_id, params) {
    const query = queryString.stringify(params);
    return [ Config.blocks_url, '/', block_id, query.length ? `?${query}` : '' ].join('');
  },

  plugin_url(path) {
    const url = new URL(path || '', Config.plugin_url, true);
    const query = { ...url.query, cid: Config.cid };
    return `${Config.plugin_url}${url.pathname}?${queryString.stringify(query)}`;
  },

  reload() {
    window.location.reload(true);
  },

  navigateTo(id, hash) {
    const params = queryString.parse(queryString.extract(location.href))
    params.selected = id;

    if (typeof hash === 'undefined' || hash === null) {
      hash = window.location.hash;
    }
    if (hash[0] === '#') {
      hash = hash.substr(1);
    }
    var oldLocation = (new URL(document.location.pathname +
                               document.location.search +
                               document.location.hash, true)).toString();

    const newLocationURL = new URL(location.href, true)
    newLocationURL.set('query', params)
    newLocationURL.set('hash', `#${hash}`)

    let newLocation = newLocationURL.toString()

    if (newLocation.substr(-1) === '#') {
      newLocation = newLocation.substr(0, newLocation.length - 1);
    }

    if (oldLocation !== newLocation) {
      document.location = newLocation;
    } else {
      this.reload();
    }
  },

  ajax,

  getView(block_id, view) {
    return ajax({
      url: this.block_url(block_id, { view }),
      dataType: 'html',
      type: 'GET'
    });
  },

  callHandler(block_id, handler, data) {

    var payload = {
      data: _.clone(data),
      handler
    };

    return ajax({
      url: this.block_url(block_id),
      type: 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      dataType: 'json'
    });
  }
};
