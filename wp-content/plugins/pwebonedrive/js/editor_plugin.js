/**
 * @version 2.0.0
 * @package OneDrive
 * @copyright © 2016 Perfect Web sp. z o.o., All rights reserved. https://www.perfect-web.co
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @author Piotr Moćko
 */

(function () {
    tinymce.create('tinymce.plugins.PWebOneDrive', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init: function (ed, url) {

            PWebOneDrive.insertTag = function (tag, file)
            {
                var tag = '[' + tag + ' id="' + file + '"]';
                this.options.editor.execCommand('mceInsertContent', 0, tag);
            };

            ed.addCommand('pwebonedrivegallery', function () {
                PWebOneDrive.browseGallery({editor: ed});
            });

            ed.addCommand('pwebonedrivefolder', function () {
                PWebOneDrive.browseFolder({editor: ed});
            });

            ed.addCommand('pwebonedrivefile', function () {
                PWebOneDrive.browseFile({editor: ed});
            });

            ed.addButton('pwebonedrivegallery', {
                title: PWebOneDrive.i18n.button_gallery,
                cmd: 'pwebonedrivegallery',
                image: url + '/../images/photo.png'
            });

            ed.addButton('pwebonedrivefolder', {
                title: PWebOneDrive.i18n.button_folder,
                cmd: 'pwebonedrivefolder',
                image: url + '/../images/folder.png'
            });

            ed.addButton('pwebonedrivefile', {
                title: PWebOneDrive.i18n.button_file,
                cmd: 'pwebonedrivefile',
                image: url + '/../images/docx.png'
            });
        },
        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl: function (n, cm) {
            return null;
        },
        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo: function () {
            return {
                longname: 'Perfect OneDrive Gallery & File Buttons',
                author: 'Piotr Moćko',
                authorurl: 'https://www.perfect-web.co/wordpress/microsoft-onedrive-gallery-file',
                infourl: 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/example',
                version: "2.0.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('pwebonedrive', tinymce.plugins.PWebOneDrive);
})();
