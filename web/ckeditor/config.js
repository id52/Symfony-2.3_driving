CKEDITOR.editorConfig = function (config) {
    config.language = 'ru';
    config.removeDialogTabs = 'image:advanced;link:advanced;flash:advanced';
    config.extraPlugins = 'video';
    config.filebrowserImageBrowseUrl = '/pdw_file_browser/index.php?editor=ckeditor&filter=image';
    config.filebrowserFlashBrowseUrl = '/pdw_file_browser/index.php?editor=ckeditor&filter=flash';
    config.filebrowserVideoBrowseUrl = '/pdw_file_browser/index.php?editor=ckeditor&filter=video';
    config.contentsCss = '/bundles/app/css/main.css';
    config.allowedContent = true;
    config.scayt_autoStartup = false;
    config.disableNativeSpellChecker = false;
    config.removePlugins = 'wsc,scayt';
};
