<?php

/**
 * @version 2.0.3
 * @package OneDrive
 * @copyright © 2016 Perfect Web sp. z o.o., All rights reserved. https://www.perfect-web.co
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @author Piotr Moćko
 */
// No direct access
function_exists('add_action') or die;

//TODO cache images for [onedrivegallery]
//TODO change prettyPhoto to some other Lightbox
//TODO image preview for [onedrivefile]
//TODO select multiple files and insert them as [onedrivefile]
//TODO load files from subfolders [onedrivegallery], [onedrivefolder]
//TODO insert album - probably not possible yet
//TODO embed video - probably not possible yet

class PWebOneDrive
{

    /**
     * @var    array  Array containing data of loaded OneDrive resources
     */
    protected static $storage = array();

    /**
     * @var    array  Array containing URL's to OneDrive resources
     */
    protected static $routes = array();

    /**
     * @var    array  Array containing information for loaded files
     */
    protected static $loaded = array();

    /**
     * Construct OneDrive object and assign WordPress actions
     */
    public function __construct()
    {
        add_action('init', array($this, 'init'));

        if (defined('DOING_AJAX'))
        {
            // Live SDK callback page
            add_action('wp_ajax_pwebonedrive_callback', array($this, 'callback'));
            add_action('wp_ajax_nopriv_pwebonedrive_callback', array($this, 'callback'));

            // Handle file download
            add_action('wp_ajax_pwebonedrive_file', array($this, 'download_file'));
            add_action('wp_ajax_nopriv_pwebonedrive_file', array($this, 'download_file'));

            // Handle image display
            add_action('wp_ajax_pwebonedrive_photo', array($this, 'display_photo'));
            add_action('wp_ajax_nopriv_pwebonedrive_photo', array($this, 'display_photo'));
        }
        else
        {
            // Shortcodes
            add_shortcode('onedrivegallery', array($this, 'galery_shortcode'));
            add_shortcode('onedrivefolder', array($this, 'folder_shortcode'));
            add_shortcode('onedrivefile', array($this, 'file_shortcode'));
        }
    }

    /**
     * Loads transaltions and registers styles and scripts
     */
    public function init()
    {
        load_plugin_textdomain('pwebonedrive', false, basename(dirname(__FILE__)) . '/languages');

        if (!defined('DOING_AJAX'))
        {
            wp_register_style('pwebonedrive_file', plugins_url('css/onedrivefile.css', __FILE__), array(), '2.0.0');
            wp_register_style('pwebonedrive_gallery', plugins_url('css/onedrivegallery.css', __FILE__), array(), '2.0.0');

            wp_register_style('pwebonedrive_prettyphoto', plugins_url('css/prettyPhoto.css', __FILE__), array(), '3.1.5');
            wp_register_script('pwebonedrive_prettyphoto', plugins_url('js/jquery.prettyPhoto' . (PWEBONEDRIVE_DEBUG ? '' : '.min') . '.js', __FILE__), array('jquery'), '3.1.5', true);
        }
    }

    /**
     * Loads CSS and JS for gallery
     */
    protected function gallery_assets()
    {
        if (!isset(self::$loaded[__METHOD__]))
        {
            wp_enqueue_style('pwebonedrive_gallery');

            // Load prettyPhoto
            wp_enqueue_style('pwebonedrive_prettyphoto');
            wp_enqueue_script('pwebonedrive_prettyphoto');

            // Init prettyPhoto
            add_action('wp_footer', array($this, 'prettyphoto_init'));

            self::$loaded[__METHOD__] = true;
        }
    }

    /**
     * Initalize prettyPhoto jQuery plug-in for gallery display
     */
    public function prettyphoto_init()
    {
        if (!isset(self::$loaded[__METHOD__]))
        {
            $options = array('deeplinking:false,social_tools:false');
            // $options[] = 'theme:"'.'"';
            // $options[] = 'overlay_gallery:false';

            echo '<script type="text/javascript">'
            . 'var oneDrivePrettyPhotoConfig={' . implode(',', $options) . '};'
            . 'jQuery(document).ready(function($){'
            . '$("a[rel^=\'onedrivegallery\']").prettyPhoto(oneDrivePrettyPhotoConfig)'
            . '});'
            . '</script>';

            self::$loaded[__METHOD__] = true;
        }
    }

    /**
     * Loads CSS and JS for files
     */
    protected function file_assets()
    {
        if (!isset(self::$loaded[__METHOD__]))
        {
            wp_enqueue_style('pwebonedrive_file');

            self::$loaded[__METHOD__] = true;
        }
    }

    /**
     * Handles gallery shortcode
     * 
     * @param array     $atts       Shortcode attributes
     *      single      = 1 - signle image which shows full gallery in Lightbox
     *      thumbnail   = thumbnail, album                  - thumbnail image size
     *      full        = full, normal                      - full image size
     *      sort        = default, updated, name, size      - sort images by
     *      sort_order  = asc, desc                         - images sort order
     *      limit       = integer                           - limit number of images
     *      offset      = integer                           - start offset
     *      class       = string                            - custom CSS class for gallery container
     * @param string    $content
     * @param string    $tag
     * 
     * @return string   gallery HTML output
     * 
     * @todo image lazy load on page scroll or pagination
     */
    public function galery_shortcode($atts, $content = null, $tag)
    {
        extract(shortcode_atts(array(
            'id' => '',
            'thumbnail' => 'thumbnail',
            'full' => 'normal',
            'single' => 0,
            'sort' => 'name',
            'sort_order' => 'asc',
            'limit' => 0,
            'offset' => 0,
            'class' => ''
                        ), $atts));

        $output = '';

        if (!$id)
            return $output;

        // Add CSS and JS
        $this->gallery_assets();

        // Images sorting
        if (!in_array($sort, array('default', 'updated', 'name', 'size')))
        {
            $full = 'name';
        }

        // Images sort order
        if ($sort_order == 'desc')
        {
            $sort_order = 'descending';
        }
        else
        {
            $sort_order = 'ascending';
        }

        $single = (int) $single;

        // Get images
        $images = $this->get_gallery($id, $sort, $sort_order, (int) $limit, (int) $offset);
        if (is_object($images) AND isset($images->data))
        {
            // Display gallery
            if (count($images->data))
            {
                // Gallery full image size
                if (!in_array($full, array('normal', 'full')))
                {
                    $full = 'normal';
                }

                // Thumbnail image size
                switch ($thumbnail)
                {
                    case 'album':
                        $index = 1;
                        break;
                    case 'thumbnail':
                    default:
                        $index = 2;
                }

                // Custom CSS class for gallery container
                if ($class)
                    $class = ' ' . $class;

                // Gallery unique ID
                $gallery_id = md5($id . time());

                // Output gallery
                $output = '<span id="onedrivegallery-' . $gallery_id . '" class="onedrivegallery' . $class . (PWEBONEDRIVE_DEBUG ? ' debug' : '') . '">';

                $i = 0;
                foreach ($images->data as $image)
                {
                    // Skip files which are not images if filtering on OneDrive failed
                    if ($image->type != 'photo')
                    {
                        continue;
                    }

                    // Image url
                    $url_vars = array(
                        'action' => 'photo',
                        'aid' => $images->access_id,
                        'code' => base64_encode($image->id . '/picture?type=' . $thumbnail)
                    );
                    $src      = self::build_route($url_vars);

                    $url_vars['code'] = base64_encode($image->id . '/picture?type=' . $full);
                    $url_vars['#']    = $image->ext;
                    $url              = self::build_route($url_vars);

                    // Output image
                    $output .= '<a href="' . $url . '"'
                            . ' rel="onedrivegallery[' . $gallery_id . ']"'
                            . ($image->description ? ' title="' . htmlentities($image->description, ENT_QUOTES, 'UTF-8') . '"' : '');
                    if (!$single || ($single && $i == 0))
                    {
                        $output .= '>'
                                . '<img src="' . $src . '"'
                                . ' width="' . $image->images[$index]->width . '" height="' . $image->images[$index]->height . '"'
                                . ' alt="' . ($image->description ? htmlentities($image->description, ENT_QUOTES, 'UTF-8') : '') . '">';
                    }
                    else
                    {
                        $output .= ' style="display:none">';
                    }
                    $output .= '</a>';

                    $i++;
                }

                $output .= '</span>';
            }
            else
            {
                // Output message about no images
                $output = '<span class="onedrivegallery-error">' . __('There are no images in this gallery!', 'pwebonedrive') . '</span>';
            }
        }
        else
        {
            // Output message about error
            $output = '<span class="onedrivegallery-error">' . __('Can not load images!', 'pwebonedrive') . (is_string($images) ? ' ' . $images : '') . '</span>';
        }

        return $output;
    }

    /**
     * Handles folder shortcode
     * 
     * @param array     $atts       Shortcode attributes
     *      icon        = 1 - show file icon before filename (default), 0 - hide
     *      size        = 1 - show formated file size after filename (default), 0 - hide
     *      open        = 1 - open file in a new tab, 0 - force download (default)
     *      sort        = default, updated, name, size      - sort files by
     *      sort_order  = asc, desc                         - files sort order
     *      limit       = integer                           - limit number of files
     *      offset      = integer                           - start offset
     *      class       = string                            - custom CSS class for folder container
     * @param string    $content
     * @param string    $tag
     * 
     * @return string   folder HTML output
     */
    public function folder_shortcode($atts, $content = null, $tag)
    {
        extract(shortcode_atts(array(
            'id' => '',
            'icon' => 1,
            'size' => 1,
            'open' => 0,
            'sort' => 'name',
            'sort_order' => 'asc',
            'limit' => 0,
            'offset' => 0,
            'class' => ''
                        ), $atts));

        $output = '';

        if (!$id)
            return $output;

        // Add CSS and JS
        $this->file_assets();

        $open = (int) $open;
        $icon = (int) $icon;
        $size = (int) $size;

        // Files sorting
        if (!in_array($sort, array('default', 'updated', 'name', 'size')))
        {
            $full = 'name';
        }

        // Files sort order
        if ($sort_order == 'desc')
        {
            $sort_order = 'descending';
        }
        else
        {
            $sort_order = 'ascending';
        }

        // Get files
        $files = $this->get_folder($id, null, $sort, $sort_order, (int) $limit, (int) $offset);
        if (is_object($files) AND isset($files->data))
        {
            // Display files
            if (count($files->data))
            {
                // Custom CSS class for files container
                if ($class)
                    $class = ' ' . $class;

                // Output files
                $output = '<ul class="onedrivefolder' . $class . (PWEBONEDRIVE_DEBUG ? ' debug' : '') . '">';

                foreach ($files->data as $file)
                {
                    // File url
                    $url_vars = array(
                        'action' => 'file',
                        'aid' => $files->access_id,
                        'code' => base64_encode($file->id . '/content' . ($open ? '' : '?download=true'))
                    );
                    if ($open)
                    {
                        $url_vars['filename'] = urlencode($file->name);
                    }

                    $url = self::build_route($url_vars);

                    // Output file
                    $output .=
                            '<li>'
                            . '<a href="' . $url . '" rel="nofollow"'
                            . ' class="onedrivefile onedrivefile-' . $file->ext . ' onedrivefile-' . $file->type . $class . (PWEBONEDRIVE_DEBUG ? ' debug' : '') . '"'
                            . ($file->description ? ' title="' . htmlentities($file->description, ENT_QUOTES, 'UTF-8') . '"' : '')
                            . ($open ? ' target="_blank"' : '')
                            . '>'
                            . ($icon ? '<span class="icon"></span>' : '')
                            . $file->name
                            . ($size ? ' <span class="size">(' . $file->size_formatted . ')</span>' : '')
                            . '</a>'
                            . '</li>';
                }
                $output .= '</ul>';
            }
            else
            {
                // Output message about no images
                $output = '<span class="onedrivegallery-error">' . __('There are no images in this gallery!', 'pwebonedrive') . '</span>';
            }
        }
        else
        {
            // Output message about error
            $output = '<span class="onedrivegallery-error">' . __('Can not load images!', 'pwebonedrive') . (is_string($images) ? ' ' . $images : '') . '</span>';
        }

        return $output;
    }

    /**
     * Handles file shortcode
     * 
     * @param array     $atts       Shortcode attributes
     *      icon    = 1 - show file icon before filename (default), 0 - hide
     *      size    = 1 - show formated file size after filename (default), 0 - hide
     *      open    = 1 - open file in a new tab, 0 - force download (default)
     *      embed   = 1 - embed file in content of page
     *      image   = album, thumbnail, normal (default), full - size of image; download - display image as link for download
     *      width   = integer   - width of image or embeded iframe
     *      height  = integer   - height of image or embeded iframe
     *      class   = string    - custom CSS class for file container
     * @param string    $content
     * @param string    $tag
     * 
     * @return string   file HTML output
     */
    public function file_shortcode($atts, $content = null, $tag)
    {
        extract(shortcode_atts(array(
            'id' => '',
            'image' => '', // Size of image: album, thumbnail, normal, full. Set: download to display image as link for download
            'width' => '', // Width attribute of img or iframe HTML tag
            'height' => '', // Height attribute of img or iframe HTML tag
            'icon' => 1, // Display icon before file name
            'size' => 1, // Display file size after file name
            'embed' => 0, // Display file content in iframe
            'open' => 0, // Open file in a new tab and do not force to download
            'class' => '' // custom CSS class for file container
                        ), $atts));

        $output = '';

        if (!$id)
            return $output;

        // Add CSS and JS
        $this->file_assets();

        $embed = (int) $embed;
        $open  = (int) $open;
        $icon  = (int) $icon;
        $size  = (int) $size;

        if ($embed)
        {
            $file = $this->get_embed_file($id);
        }
        else
        {
            $file = $this->get_file($id);
        }

        if (is_object($file))
        {
            if ($class)
                $class = ' ' . $class;

            // Display embed file content
            if ($embed AND isset($file->embed_html) AND $file->embed_html)
            {
                $output = $file->embed_html;
                if ($width)
                {
                    $output = preg_replace('/width="[^"]+"/i', 'width="' . $width . '"', $output);
                }
                if ($height)
                {
                    $output = preg_replace('/height="[^"]+"/i', 'height="' . $height . '"', $output);
                }

                // Output embed file
                $output = '<span class="onedrivefile-embed' . $class . (PWEBONEDRIVE_DEBUG ? ' debug' : '') . '">'
                        . $output
                        . '</span>';
            }
            // Display photo
            elseif ($file->type == 'photo' AND $image != 'download')
            {
                if (!in_array($image, array('normal', 'album', 'thumbnail', 'full')))
                    $image = 'normal';

                if ($content)
                {
                    $file->description = $content;
                }

                // Image url
                $url_vars = array(
                    'action' => 'photo',
                    'aid' => $file->access_id,
                    'code' => base64_encode($file->id . '/picture?type=' . $image)
                );
                $src      = self::build_route($url_vars);

                // Output image
                $output = '<img src="' . $src . '" class="onedrivefile onedrivefile-photo' . $class . (PWEBONEDRIVE_DEBUG ? ' debug' : '') . '"';
                if ($width OR $height)
                {
                    if ($width)
                        $output .= ' width="' . $width . '"';
                    if ($height)
                        $output .= ' height="' . $height . '"';
                }
                else
                {
                    // Select image size
                    switch ($image)
                    {
                        case 'thumbnail':
                            $index = 2;
                            break;
                        case 'normal':
                            $index = 0;
                            break;
                        case 'full':
                            $index = 3;
                            break;
                        case 'album':
                        default:
                            $index = 1;
                    }
                    $output .= ' width="' . $file->images[$index]->width . '" height="' . $file->images[$index]->height . '"';
                }
                $output .= ' alt="' . htmlentities($file->description, ENT_QUOTES, 'UTF-8') . '">';
            }
            // Display file link
            else
            {
                if ($content)
                {
                    $file->name = $content;
                }

                // File url
                $url_vars = array(
                    'action' => 'file',
                    'aid' => $file->access_id,
                    'code' => base64_encode($file->id . '/content' . ($open ? '' : '?download=true'))
                );
                if ($open)
                {
                    $url_vars['filename'] = urlencode($file->name);
                }

                $url = self::build_route($url_vars);

                // Output file
                $output = '<a href="' . $url . '" rel="nofollow"'
                        . ' class="onedrivefile onedrivefile-' . $file->ext . ' onedrivefile-' . $file->type . $class . (PWEBONEDRIVE_DEBUG ? ' debug' : '') . '"'
                        . ($file->description ? ' title="' . htmlentities($file->description, ENT_QUOTES, 'UTF-8') . '"' : '')
                        . ($open ? ' target="_blank"' : '')
                        . '>'
                        . ($icon ? '<span class="icon"></span>' : '')
                        . $file->name
                        . ($size ? ' <span class="size">(' . $file->size_formatted . ')</span>' : '')
                        . '</a>';
            }
        }
        else
        {
            // Output message about error
            $output = '<span class="onedrivefile-error">' . __('Can not load file!', 'pwebonedrive') . (is_string($file) ? ' ' . $file : '') . '</span>';
        }

        return $output;
    }

    /**
     * Get gallery data from OneDrive account for given $resource_id
     * 
     * @see get_folder()
     * 
     * @param string $resource_id   OneDrive resource ID
     * @param string $sort_by       Sort images by
     * @param string $sort_order    Images sort order
     * @param integer $limit        Number of files to get
     * @param integer $offset       Start offset
     * 
     * @return mixed                false on error, string with error message or object with resource data on success
     */
    protected function get_gallery($resource_id, $sort_by = 'name', $sort_order = 'ascending', $limit = null, $offset = null)
    {
        return $this->get_folder($resource_id, 'photos', $sort_by, $sort_order, $limit, $offset);
    }

    /**
     * Get files data from OneDrive folder for given $resource_id
     * 
     * @param string $resource_id   OneDrive resource ID
     * @param string $filter        Filter files by type: all (default), photos, videos, audio, folders, or albums
     * @param string $sort_by       Sort images by: updated, name (default), size, or default
     * @param string $sort_order    Files sort order: ascending or descending
     * @param integer $limit        Number of files to get
     * @param integer $offset       Start offset
     * 
     * @return mixed                false on error, string with error message or object with resource data on success
     */
    protected function get_folder($resource_id, $filter = null, $sort_by = 'name', $sort_order = 'ascending', $limit = null, $offset = null)
    {
        if (isset(self::$storage[$resource_id]))
        {
            return self::$storage[$resource_id];
        }

        $client = LiveConnectClient::getInstance();
        $client->setOption('usecookie', false);

        $client->log(__METHOD__ . '. Get Files' . ( $filter ? ' type of: ' . $filter : '' ) . ' from Folder ID: ' . $resource_id);

        // Get photos
        $response = $client->queryByRersourceId($resource_id, $resource_id . '/files?sort_by=' . $sort_by . '&sort_order=' . $sort_order
                . ( $filter ? '&filter=' . $filter : '' )
                . ( $limit ? '&limit=' . $limit : '' )
                . ( $offset ? '&offset=' . $offset : '' )
        );
        if (is_wp_error($response))
        {
            $client->log(__METHOD__ . '. Get Files from Folder REST error: ' . $response->get_error_message(), E_USER_ERROR);
            return __('Can not load data!', 'pwebonedrive') . ' ' . $this->get_error_message($response);
        }

        $data = $response['body'];
        if (!$data)
            return false;

        if (isset($data->data))
        {
            $client->log(__METHOD__ . '. Files from Folder loaded');

            // Access Id
            $data->access_id = $client->getAccessId();

            foreach ($data->data as &$file)
            {
                // File extension
                $dot       = strrpos($file->name, '.') + 1;
                $file->ext = substr($file->name, $dot);

                // Formatted file size
                $file->size_formatted = $this->format_file_size($file->size);
            }

            self::$storage[$resource_id] = $data;
            return self::$storage[$resource_id];
        }
        elseif (isset($data->error) AND isset($data->error->message))
        {
            $client->log(__METHOD__ . '. Get Files from Folder REST error: ' . $data->error->message, E_USER_ERROR);
            return __('Can not load data!', 'pwebonedrive') . ' ' . $data->error->message;
        }

        return false;
    }

    /**
     * Get single file data from OneDrive account for given $resource_id
     * 
     * @param type $resource_id     OneDrive resource ID
     * 
     * @return mixed                false on error, string with error message or object with resource data on success
     */
    protected function get_file($resource_id)
    {
        if (isset(self::$storage[$resource_id]))
        {
            return self::$storage[$resource_id];
        }

        $client = LiveConnectClient::getInstance();
        $client->setOption('usecookie', false);

        $client->log(__METHOD__ . '. Get single File by ID: ' . $resource_id);

        // Get File
        $response = $client->queryByRersourceId($resource_id);
        if (is_wp_error($response))
        {
            $client->log(__METHOD__ . '. Get single File REST error: ' . $response->get_error_message(), E_USER_ERROR);
            return __('Can not load data!', 'pwebonedrive') . ' ' . $this->get_error_message($response);
        }

        $data = $response['body'];
        if (!$data)
            return false;

        if (isset($data->id))
        {
            $client->log(__METHOD__ . '. Single File loaded');

            // File extension
            $dot       = strrpos($data->name, '.') + 1;
            $data->ext = substr($data->name, $dot);

            // Access Id
            $data->access_id = $client->getAccessId();

            // Formatted file size
            $data->size_formatted = $this->format_file_size($data->size);

            self::$storage[$resource_id] = $data;
            return self::$storage[$resource_id];
        }
        elseif (isset($data->error) AND isset($data->error->message))
        {
            $client->log(__METHOD__ . '. Get single File REST error: ' . $data->error->message, E_USER_ERROR);
            return __('Can not load data!', 'pwebonedrive') . ' ' . $data->error->message;
        }

        return false;
    }

    /**
     * Get single file data with embed HTML code from OneDrive account for given $resource_id
     * 
     * @param type $resource_id     OneDrive resource ID
     * 
     * @return mixed                false on error, string with error message or object with resource data on success
     */
    protected function get_embed_file($resource_id)
    {
        if (isset(self::$storage[$resource_id]))
        {
            if (self::$storage[$resource_id]->is_embeddable AND isset(self::$storage[$resource_id]->embed_html))
            {
                return self::$storage[$resource_id];
            }
            else
            {
                return self::$storage[$resource_id];
            }
        }

        $file = $this->get_file($resource_id);
        if (!is_object($file))
        {
            return false;
        }
        if (!$file->is_embeddable)
        {
            return $file;
        }

        $client = LiveConnectClient::getInstance();
        $client->setOption('usecookie', false);

        $client->log(__METHOD__ . '. Get embed File by ID: ' . $resource_id);

        // Get embed File
        $response = $client->queryByRersourceId($resource_id, $resource_id . '/embed');
        if (is_wp_error($response))
        {
            $client->log(__METHOD__ . '. Get embed File REST error: ' . $response->get_error_message(), E_USER_ERROR);
            return __('Can not load data!', 'pwebonedrive') . ' ' . $this->get_error_message($response);
        }

        $data = $response['body'];
        if (!$data)
            return false;

        if (isset($data->embed_html))
        {
            $client->log(__METHOD__ . '. Embed File loaded');

            self::$storage[$resource_id]->embed_html = $data->embed_html;

            return self::$storage[$resource_id];
        }
        elseif (isset($data->error) AND isset($data->error->message))
        {
            $client->log(__METHOD__ . '. Get embed File REST error: ' . $data->error->message, E_USER_ERROR);
            return __('Can not load data!', 'pwebonedrive') . ' ' . $data->error->message;
        }

        return false;
    }

    /**
     * Get error message from WP_Error returned by WP_Request
     * 
     * @param WP_Error $request Error object
     * @return string           Error message
     */
    protected function get_error_mesaage($request)
    {
        $message = $request->get_error_mesaage();

        $start = strpos($message, '{');
        $end   = strrpos($message, '}');

        if ($start !== false AND $end !== false)
        {
            $data = json_decode(substr($message, $start, $end - $start));
            if (isset($data->error) AND isset($data->error->message))
            {
                return $data->error->message;
            }
        }

        return $message;
    }

    /**
     * Format file size
     * 
     * @param float $size   File size
     * 
     * @return string       Formatted file size
     */
    protected function format_file_size($size)
    {
        $base = log($size, 2);

        if ($base >= 30)
        {
            $div   = 1024 * 1024 * 1024;
            $sufix = ' GB';
        }
        elseif ($base >= 20)
        {
            $div   = 1024 * 1024;
            $sufix = ' MB';
        }
        elseif ($base >= 10)
        {
            $div   = 1024;
            $sufix = ' KB';
        }
        else
        {
            return $size . ' B';
        }

        $size = $size / $div;
        return round($size, $size < 50 ? 1 : 0) . $sufix;
    }

    /**
     * Download file from OneDrive account
     * 
     * integer $_GET['aid']         Access ID from database for given resource
     * base64  $_GET['code']        REST request path
     * string  $_GET['filename']    Filename for content displayed in a new browser tab
     */
    public function download_file()
    {
        $client = LiveConnectClient::getInstance();
        $client->setOption('usecookie', false);

        $client->log(__METHOD__);

        $access_id = isset($_GET['aid']) ? (int) $_GET['aid'] : 0;
        $url       = isset($_GET['code']) ? base64_decode($_GET['code']) : null;

        if (!$url OR ! $access_id)
            die();

        // Get File
        $response = $client->queryByAccessId($access_id, $url);
        if (is_wp_error($response))
        {
            $client->log(__METHOD__ . '. REST error: ' . $response->get_error_message(), E_USER_ERROR);
            die(__('Can not load data!', 'pwebonedrive') . ' Request error: ' . $this->get_error_message($response));
        }

        // Follow location returned by request
        if (headers_sent() AND isset($response['headers']['location']))
        {
            echo "<script>document.location.href='" . htmlspecialchars($response['headers']['location']) . "';</script>\n";
        }
        else
        {
            unset($response['headers']['keep-alive']);

            foreach ($response['headers'] as $name => $value)
            {
                header($name . ': ' . $value);
            }

            $filename = isset($_GET['filename']) ? ltrim(basename(urldecode($_GET['filename'])), '.|~') : null;
            if ($filename)
            {
                header('Content-Disposition: inline; filename="' . $filename . '"');
            }

            echo $response['body'];
        }

        die();
    }

    //TODO merge with download_file()
    /**
     * Display photo from OneDrive account
     * 
     * integer $_GET['aid']         Access ID from database for given resource
     * base64  $_GET['code']        REST request path
     */
    public function display_photo()
    {
        $client = LiveConnectClient::getInstance();
        $client->setOption('usecookie', false);

        $client->log(__METHOD__);

        $access_id = isset($_GET['aid']) ? (int) $_GET['aid'] : 0;
        $url       = isset($_GET['code']) ? base64_decode($_GET['code']) : null;

        if (!$url OR ! $access_id)
            die();

        // Get File
        $response = $client->queryByAccessId($access_id, $url);
        if (is_wp_error($response))
        {
            $client->log(__METHOD__ . '. REST error: ' . $response->get_error_message(), E_USER_ERROR);
            die(__('Can not load data!', 'pwebonedrive') . ' Request error: ' . $this->get_error_message($response));
        }

        // Follow location returned by request
        if (headers_sent() AND isset($response['headers']['location']))
        {
            echo "<script>document.location.href='" . htmlspecialchars($response['headers']['location']) . "';</script>\n";
        }
        else
        {
            if ($response['body'])
            {
                unset($response['headers']['location'], $response['headers']['keep-alive']);
            }
            elseif (false)
            { //TODO option: image_redirect
                // Get image from location and output to the browser instead of redirecting to that location
                $url = $response['headers']['location'];
                unset($response['headers']['location'], $response['headers']['keep-alive']);

                $response = $client->request($url, $response['headers']);
                if (is_wp_error($response))
                {
                    die(__('Can not load data!', 'pwebonedrive') . ' Request error: ' . $response->get_error_message());
                }
            }

            foreach ($response['headers'] as $name => $value)
            {
                header($name . ': ' . $value);
            }

            echo $response['body'];
        }

        die();
    }

    /**
     * Build URL for given request variables
     * 
     * @global WP_Rewrite $wp_rewrite
     * 
     * @param array $vars           associative array of key=value pars for URL
     * @param boolean $ssl          NULL - auto mode, TRUE - force HTTPS, FALSE - force HTTP
     * @param integer $permalinks   NULL - auto mode, 0 - not using permalinks, 1 - using permalinks, 2 - using mod_rewrite permalinks
     * 
     * @return string
     */
    public static function build_route($vars = array(), $ssl = null, $permalinks = null)
    {
        global $wp_rewrite;

        if ($permalinks === null)
        {
            $cache_key = md5(serialize($vars));
            if (isset(self::$routes[$cache_key]))
            {
                return self::$routes[$cache_key];
            }
        }

        if ($ssl === null)
        {
            $client_id = get_option('pweb_onedrive_client_id');
            $ssl = (empty($client_id) || strpos($client_id, '-') !== false || strlen($client_id) > 16) ? true : is_ssl();
        }

        if (($wp_rewrite->using_permalinks() AND $permalinks === null) OR $permalinks > 0)
        {
            $segments = array(
                'pwebonedrive',
                $vars['action']
            );
            unset($vars['action']);

            if (isset($vars['aid']))
            {
                $segments[] = $vars['aid'];
                unset($vars['aid']);
            }
            if (isset($vars['filename']))
            {
                $segments[] = $vars['filename'];
                unset($vars['filename']);
            }

            $fragment = '';
            if (isset($vars['#']))
            {
                $fragment = '#' . $vars['#'];
                unset($vars['#']);
            }

            $query = '';
            if (count($vars))
            {
                foreach ($vars as $var => $value)
                {
                    $query[] = $var . '=' . $value;
                }
                $query = implode('&', $query);
            }

            $url = home_url(
                    ( (($wp_rewrite->using_mod_rewrite_permalinks() AND $permalinks === null) OR $permalinks === 2) ? '' : $wp_rewrite->index . '/')
                    . implode('/', $segments)
                    . ($query ? '/?' . $query : '')
                    . $fragment, $ssl ? 'https' : 'http'
            );
        }
        else
        {

            if ($vars['action'] == 'callback')
            {
                $url = plugins_url('callback.php', __FILE__);
            }
            else
            {
                $action = $vars['action'];
                unset($vars['action']);

                $fragment = '';
                if (isset($vars['#']))
                {
                    $fragment = '#' . $vars['#'];
                    unset($vars['#']);
                }

                $query = '';
                if (count($vars))
                {
                    foreach ($vars as $var => $value)
                    {
                        $query[] = $var . '=' . $value;
                    }
                    $query = implode('&', $query);
                }

                $url = admin_url('admin-ajax.php?action=pwebonedrive_' . $action
                        . ($query ? '&' . $query : '')
                        . $fragment, $ssl ? 'https' : 'http'
                );
            }
        }

        if ($permalinks === null)
        {
            self::$routes[$cache_key] = $url;
        }

        return $url;
    }

    /**
     * Handle Live SDK callback page
     */
    public function callback()
    {
        $client = LiveConnectClient::getInstance();
        $client->log(__METHOD__);

        echo $client->handlePageRequest();

        $client->log(__METHOD__ . '. Die');

        die();
    }

    /**
     * Install database tables: onedrive_access and onedrive_storage
     * 
     * @global object $wpdb
     * @global string $charset_collate
     */
    public static function install()
    {
        global $wpdb;
        global $charset_collate;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}onedrive_access` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `user_id` varchar(2048) DEFAULT NULL,
		  `access_token` varchar(2048) DEFAULT NULL,
		  `refresh_token` varchar(2048) DEFAULT NULL,
		  `created` int(11) unsigned DEFAULT NULL,
		  `expires_in` int(6) DEFAULT '3600',
		  PRIMARY KEY (`id`),
		  KEY `user` (`user_id`(333))
		) $charset_collate AUTO_INCREMENT=1;";

        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}onedrive_storage` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `resource_id` varchar(2048) NOT NULL,
		  `access_id` int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `resource` (`resource_id`(333)),
		  KEY `idx_access_id` (`access_id`)
		) $charset_collate AUTO_INCREMENT=1;";

        dbDelta($sql);
    }

    /**
     * Drop database tables: onedrive_access and onedrive_storage
     * and delete plug-in options
     * 
     * @global object $wpdb
     */
    public static function uninstall()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}onedrive_access`");
        $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}onedrive_storage`");

        delete_option('pweb_onedrive_client_id');
        delete_option('pweb_onedrive_client_secret');
    }

}
