import 'babel-polyfill'
import $ from 'jquery'
import Backbone from 'backbone'
import helper from './url'
import BlockModel from './block_model'

import '../less/courseware.less'

import Courseware from 'Courseware/js/Courseware'
import 'AssortBlock/js/AssortBlock'
import 'AudioBlock/js/AudioBlock'
import 'AudioGalleryBlock/js/AudioGalleryBlock'
import 'BeforeAfterBlock/js/BeforeAfterBlock'
import 'BlubberBlock/js/BlubberBlock'
import 'CanvasBlock/js/CanvasBlock'
import 'ChartBlock/js/ChartBlock'
import 'CodeBlock/js/CodeBlock'
import 'ConfirmBlock/js/ConfirmBlock'
import 'DateBlock/js/DateBlock'
import 'DialogCardsBlock/js/DialogCardsBlock'
import 'DiscussionBlock/js/DiscussionBlock'
import 'DownloadBlock/js/DownloadBlock'
import 'EmbedBlock/js/EmbedBlock'
import 'EvaluationBlock/js/EvaluationBlock'
import 'FolderBlock/js/FolderBlock'
import 'ForumBlock/js/ForumBlock'
import 'GalleryBlock/js/GalleryBlock'
import 'HtmlBlock/js/HtmlBlock'
import 'IFrameBlock/js/IFrameBlock'
import 'ImageMapBlock/js/ImageMapBlock'
import 'InteractiveVideoBlock/js/InteractiveVideoBlock'
import 'KeyPointBlock/js/KeyPointBlock'
import 'LinkBlock/js/LinkBlock'
import 'OpenCastBlock/js/OpenCastBlock'
import 'PdfBlock/js/PdfBlock'
import 'PostBlock/js/PostBlock'
import 'ScrollyBlock/js/ScrollyBlock'
import 'SearchBlock/js/SearchBlock'
import 'Section/js/Section'
import 'TestBlock/js/TestBlock'
import 'TypewriterBlock/js/TypewriterBlock'
import 'VideoBlock/js/VideoBlock'

import 'PortfolioBlock/js/PortfolioBlock'
import 'PortfolioBlockSupervisor/js/PortfolioBlockSupervisor'
import 'PortfolioBlockUser/js/PortfolioBlockUser'

$(document).ready(function () {

  function logError(error) {
    if (console) {
      console.log(error);
    }
  }

  window.onerror = function (message, file, line) {
    logError(file + ':' + line + '\n\n' + message);
  };

  patchBackbone();

  Backbone.history.start({
    push_state: true,
    silent: true,
    root: helper.courseware_url
  });

  const el = $('#courseware');

  const model = new BlockModel({
    id: el.attr('data-blockid'),
    type: 'Courseware'
  });

  Courseware.createView('student', { el, model });
});

function patchBackbone() {

  // Backbone.ajax to return native ES6 promises for ajax calls insead of jQuery.Deferreds
  Backbone.origAjax = Backbone.ajax;
  Backbone.ajax = function ajax() {
    return Promise.resolve(Backbone.$.ajax.apply(Backbone.$, arguments));
  }

  // Backbone.sync to resolve with models/collections as the settlement argument.
  Backbone.origSync = Backbone.sync;
  Backbone.sync = function sync(method, model, options) {
    return Backbone.origSync(method, model, options)
      .then(function resolveWithModel() { return model});
  };

  // Model.prototype.save to reject on validity errors, NOT return false.
  var origSave = Backbone.Model.prototype.save;
  Backbone.Model.prototype.save = function save() {
    var xhr = origSave.apply(this, arguments);
    // By this point, orgSave has typically validated the model (emitting 'invalid' event),
    // and sync would have caught xhr errors (rejecting with xhr, and emitting 'error' event).
    // Rejecting with Model error instead of false.
    return (xhr !== false) ? xhr : Promise.reject(new Error('ModelError'));
  };

  // Model.prototype.destroy to reject on errors, NOT return false.
  var origDestroy = Backbone.Model.prototype.destroy;
  Backbone.Model.prototype.destroy = function destroy() {
    var xhr = origDestroy.apply(this, arguments);
    return (xhr !== false) ? xhr : Promise.reject(new Error('ModelError'));
  };
}
