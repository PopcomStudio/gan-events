// https://www.tiny.cloud/blog/loving-the-tinymce-menu

// Import TinyMCE
import tinymce from 'tinymce/tinymce';
// import 'tinymce-i18n/langs5/fr_FR';

// Default icons are required for TinyMCE 5.3 or above
import 'tinymce/icons/default';

// A theme is also required
import 'tinymce/themes/silver';

// Any plugins you want to use has to be imported
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/paste';
import 'tinymce/plugins/image';

/**
 * TinyMCE 4 insert link form fields disabled
 * @url https://stackoverflow.com/questions/33618361/tinymce-4-insert-link-form-fields-disabled
 */
$(document).on('focusin', function(e) {
    var target = $(e.target);
    if (target.closest(".mce-window").length || target.closest(".tox-dialog").length) {
        e.stopImmediatePropagation();
        target = null;
    }
});

function fileManagerBrowser (callback, value, meta) {
    // Get file manager URL from global variable
    let url = '/dashboard/admin/filemanager/browse/event/1'; // Default URL
    
    if (typeof window.filemanager_url !== "undefined" && window.filemanager_url) {
        url = window.filemanager_url;
    }
    
    tinymce.activeEditor.windowManager.openUrl({
        url: url,
        title: 'Gestionnaire de fichiers',
        width: 900,
        height: 600,
        onMessage: function (dialogApi, details) {
            if (details.mceAction === 'fileSelected') {
                const fileUrl = details.data.url;
                callback(fileUrl);
                dialogApi.close();
            }
        },
    });
}

var tinymce_conf = {
    language: 'fr_FR',
    skin_url: '/skins/ui/oxide',
    content_css: '/skins/content/default/content.min.css',
    menubar: false,
    statusbar: false,
    contextmenu: 'copy paste | link | bold italic',
    plugins: ['paste', 'link', 'lists', 'autoresize', 'image'],
    toolbar: [
        'bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | '+
        'numlist bullist | link unlink | sup sub '
    ],
    file_picker_callback : fileManagerBrowser,
    branding: false,
    paste_block_drop: true,
    paste_data_images: false,
    // paste_as_text: true,
    paste_word_valid_elements: 'p, b, strong, i, em, ul, li',
    paste_retain_style_properties: '',
    // paste_remove_styles_if_webkit: true,
    paste_merge_formats: true,
    min_height: 300,
    autoresize_bottom_margin: 10,
    lists_indent_on_tab: true,
    setup: function (editor) {
        editor.on('change', function () {
                tinymce.triggerSave();
            }
        );
    },
    convert_urls: false,
    target_list: false,
};

tinymce.init(Object.assign(tinymce_conf, {selector: 'textarea[data-init="wysiwyg"]'}));

global.tinymce_reload = function() {
    for (var i = tinymce.editors.length - 1 ; i > -1 ; i--) {
        var ed_id = tinymce.editors[i].id;
        tinymce.execCommand("mceRemoveEditor", true, ed_id);
    }
    tinymce.init(Object.assign(tinymce_conf, {selector: 'textarea[data-init="wysiwyg"]'}));
}