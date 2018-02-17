<?php

/**
 * The main class for controllers
 *
 */
class HMU_Classes_FrontController {

    /** @var object of the model class */
    public $model;

    /** @var object of the view class */
    public $view;

    /** @var string name of the  class */
    protected $name;

    public function __construct() {

        /* Load error class */
        HMU_Classes_ObjController::getClass('HMU_Classes_Error');

        /* Load Tools */
        HMU_Classes_ObjController::getClass('HMU_Classes_Tools');

        /* get the name of the current class */
        $this->name = get_class($this);

        /* load the model and hooks here for wordpress actions to take efect */
        /* create the model and view instances */
        $this->model = HMU_Classes_ObjController::getClass(str_replace('Controllers', 'Models', $this->name));

        //IMPORTANT TO LOAD HOOKS HERE
        /* check if there is a hook defined in the controller clients class */
        HMU_Classes_ObjController::getClass('HMU_Classes_HookController')->setHooks($this);



        /* Load the Main classes Actions Handler */
        HMU_Classes_ObjController::getClass('HMU_Classes_Action');
        HMU_Classes_ObjController::getClass('HMU_Classes_DisplayController');

        //abstract classes
        HMU_Classes_ObjController::getClass('HMU_Models_Abstract_Domain');
        HMU_Classes_ObjController::getClass('HMU_Models_Abstract_Models');
    }

    /**
     * load sequence of classes
     * Function called usualy when the controller is loaded in WP
     *
     * @return HMU_Classes_FrontController
     */
    public function init() {
        return $this;
    }

    /**
     *
     * Get the block view
     *
     * @param null $view
     * @return mixed
     */
    public function getView($view = null) {
        if (!isset($view)) {
            if ($class = HMU_Classes_ObjController::getClassPath($this->name)) {
                $view = $class['name'];
            }
        }

        if (isset($view)) {
            $this->view = HMU_Classes_ObjController::getClass('HMU_Classes_DisplayController');
            return $this->view->getView($view, $this);
        }

        return '';
    }

    /**
     * Called as menu callback to show the block
     *
     */
    public function show() {
        echo $this->init()->getView();
    }

    /**
     * first function call for any class
     *
     */
    protected function action() {
        // generated nonce we created
        if (function_exists('wp_verify_nonce'))
            if (!wp_verify_nonce(HMU_Classes_Tools::getValue('hmu_nonce'), _HMU_NONCE_ID_))
                die('Invalid request!');
    }

    /**
     * initialize settings
     * Called from index
     *
     * @return void
     */
    public function runAdmin() {
        HMU_Classes_ObjController::getClass('HMU_Controllers_Rewrite');
        /* show the admin menu and post actions */
        HMU_Classes_ObjController::getClass('HMU_Controllers_Menu');
    }

    /**
     * Called on frontend. For disconnected users
     */
    public function runFrontend() {
        HMU_Classes_ObjController::getClass('HMU_Controllers_Rewrite');
    }

    /**
     * check the user to be active in order to access the admin panel
     *
     * Called by wordpress on Admin Init
     */
    public function hookInit() {

    }

    /**
     * Hook the admin head
     * This function will load the media in the header for each class
     *
     * @return void
     */
    public function hookHead() {

    }

}
