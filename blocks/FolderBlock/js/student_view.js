import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import templates from 'js/templates'

export default StudentView.extend({
  events: {
    'click button[name=upload]': 'fileUpload',
    'click button.button[type=submit]': 'saveLicenses',
    'click a.cancel.button': 'cancelLicenses',
    'click button[name=unzip]': 'unzipFile',
    'click button[name=dontunzip]': 'dontunzipFile',
    'click .cw-folder-select-button': 'triggerFileSelector',
    'click .cw-folder-reselect-button': 'triggerFileSelector',
    'change .cw-folder-file-upload': 'updateFileSelection'
  },

  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
    let fileCounter = this.$('input[name="file_counter"]').val();
    let allowUpload = this.$('input[name="allow_upload"]').val();
    if ((fileCounter == 0) && (allowUpload != 1)){
      this.$('.cw-folder-title').hide();
      this.$('.cw-folder').hide();
    }
    let dummy = this.$('.documents.dummy-table').get(0);
    if (dummy) {
      dummy.config = {};
      dummy.config.sortList = {};
    }
    STUDIP.Files.filesapp = {};
    STUDIP.Files.filesapp.files = {};
    STUDIP.Files.filesapp.files = [];
  },

  fileUpload() {
    var files = 0,
        filelist = this.$('.cw-folder-file-upload')[0].files,
        folder_id = this.$('input[name="folder_id"]').val(),
        data = new FormData(),
        view = this;

    $.each(filelist, function (index, file) {
      if (STUDIP.Files.validateUpload(file)) {
        data.append('file[]', file, file.name);
        files += 1;
      } else {
        alert(file.name + 'ist zu groß oder hat eine nicht erlaubte Endung.')
      }
    });

    if(files > 0) {
      this.setupModel();
      $.get(STUDIP.URLHelper.getURL('dispatch.php/file/upload_window'), function (data) {
        view.$el.find('.cw-folder').html(data);
        $('.file_uploader').show();
      });
      $.ajax({
        url: STUDIP.URLHelper.getURL('dispatch.php/file/upload/' + folder_id),
        data: data,
        cache: false,
        contentType: false,
        processData: false,
        type: 'POST',
        xhr: function () {
          var xhr = $.ajaxSettings.xhr();
          if (xhr.upload) {
            xhr.upload.addEventListener('progress', function (event) {
              var percent = 0,
                  position = event.loaded || event.position,
                  total = event.total;
              if (event.lengthComputable) {
                percent = Math.ceil(position / total * 100);
              }
              view.$('.file_upload_window .uploadbar').css('background-size', percent + '% 100%');
            }, false);
          }
          return xhr;
        }
      }).done(function (json) {
        view.$('.file_upload_window .uploadbar').css('background-size', '100% 100%');
        if (json.redirect) {
          $.get(json.redirect,function (data) {
            view.$el.find('.cw-folder').html(data);
          })
        } 
        if(json.message) {
          view.$('.errorbox').show().html(json.message);
          view.$('.file_upload_window .uploadbar').hide();
        }
        if(json.added_files) {
          view.reloadFiles();
        }
      });
    }
  },

  unzipFile(event) {
    event.preventDefault();
    this.unzipEvent(true);
  },

  dontunzipFile(event) {
    event.preventDefault();
    this.unzipEvent(false);
  },

  saveLicenses(event) {
    event.preventDefault();
    this.licenseEvent(true);
  },

  cancelLicenses(event) {
    event.preventDefault();
    this.licenseEvent(false);
  },

  setupModel() {
    this.model.set('files', this.getFilesFromFolder());
    this.model.set('folder_available', true);
    this.model.set('folder_id', this.$('input[name="folder_id"]').val());
    this.model.set('folder_title', this.$('input[name="folder_title"]').val());
    this.model.set('folder_name', this.$('input[name="folder_name"]').val());
    this.model.set('allow_upload', this.$('input[name="allow_upload"]').val());
    this.model.set('viewable', true)
  },

  getFilesFromFolder() {
    var files = [];
    this.$('.cw-folder-hidden-files').children().each(function () {
      var file = { 'id': this.attributes['file-id'].value, 
        'name': this.attributes['file-name'].value, 
        'icon': this.attributes['file-icon'].value, 
        'url': this.attributes['file-url'].value,
        'downloadable': this.attributes['file-downloadable'].value };
      files.push(file);
    });
    return files;
  },

  reloadFiles() {
    let view = this;
    helper
      .callHandler(this.model.id, 'reload', { })
      .then(function (response) {
        view.model.set('files', response.files);
        view.model.set('homework_files', response.homework_files);
        view.$el.html(templates('FolderBlock', 'student_view', { ...view.model.attributes }));
        view.postRender();
      }).catch(function (error) {
        console.log(error);
      });
  },

  unzipEvent(unzip) {
    var data = new FormData(),
        form = this.$('form')[0],
        view = this;
    
    data.append('file_refs[]', form[0].value)
    if(unzip) {
      data.append('unzip', true);
    }
    $.ajax({
      type: 'POST',
      url: form.action,
      data: data,
      cache: false,
      contentType: false,
      processData: false
    }).done(function (data) {
      view.$el.find('.cw-folder').html(data);
    });
  },

  licenseEvent(set_license) {
    var data = new FormData(),
        form = this.$('form')[0],
        view = this;

    $('form input[name="file_refs[]"]').each(function () {
      data.append('file_refs[]', this.value)
    })

    data.append('content_terms_of_use_id', set_license ? this.$('input[name="content_terms_of_use_id"]:checked').val() : 'UNDEF_LICENSE')
    $.ajax({
      type: 'POST',
      url: form.action,
      data: data,
      cache: false,
      contentType: false,
      processData: false
    }).done(function (data) {
      view.reloadFiles();
      //view.updateView(data['html']);
    });
  },

  // updateView(data) {
  //   var view = this,
  //       files = [];
  //   data.forEach(function (entry) {
  //     var file = {
  //       'id': entry.match(/id="fileref_(.*)\"/)[1],
  //       'name': entry.match(/<td data-sort-value=\"(.*)\">/)[1],
  //       'icon': entry.match(/alt=\"file-(.*?)\"/)[1],
  //       'url': entry.match(/<a href=\"(.*?)\"/)[1].replace(/&amp;/g,'&').replace('sendfile.php?', 'sendfile.php?force_download=1&'),
  //       'downloadable': '1'
  //     };
  //     files.push(file);
  //   });
  //   files = this.model.get('files').concat(files);
  //   files.sort(function (a,b) {
  //     return a['name'].localeCompare(b['name'])
  //   })
  //   this.model.set('files', files);
  //   this.$el.html(templates('FolderBlock', 'student_view', { ...this.model.attributes }));
  // },

  triggerFileSelector() {
    this.$('.cw-folder-file-upload').click();
  },

  updateFileSelection() {
    let files = (this.$('.cw-folder-file-upload')[0]).files;
    let fileName = this.$('.cw-folder-upload-filename');
    if (files) {
      fileName.text(files[0].name);
      this.$('.cw-folder-select-button').hide();
      this.$('.cw-folder-reselect-button').show();
      this.$('.cw-folder-upload-button').show();
      this.$('.cw-folder-upload-filename').show();
    }
  }
});
