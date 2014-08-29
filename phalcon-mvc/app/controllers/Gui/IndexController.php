<?php

/**
 * Gui controller
 *
 * index page
 */
class IndexController extends ControllerBase
{

        public function initialize()
        {
                $this->view->setTemplateAfter('main');
                Phalcon\Tag::setTitle('Home');
                parent::initialize();
        }

        public function indexAction()
        {
                /* index action */
                $this->flash->notice('Showing this message from index action');
        }

}
