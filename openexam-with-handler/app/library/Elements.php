<?php

/**
 * Elements
 *
 * Helps to build UI elements for the application
 */
class Elements extends Phalcon\Mvc\User\Component
{

        private $_headerMenu = array(
                'pull-left'  => array(
                        'index' => array(
                                'caption' => 'Home',
                                'action'  => 'index'
                        ),
                        'exam'  => array(
                                'caption' => 'Create Exam',
                                'action'  => 'index'
                        ),
                ),
                'pull-right' => array(
                        'auth' => array(
                                'caption' => 'Log In',
                                'action'  => 'index'
                        ),
                )
        );

        /**
         * Builds header menu with left and right items
         *
         * @return string
         */
        public function getMenu()
        {

                $auth = $this->session->get('auth');
                if ($auth) {
                        $this->_headerMenu['pull-right']['auth'] = array(
                                'caption' => 'Log Out',
                                'action'  => 'end'
                        );
                } else {
                        unset($this->_headerMenu['pull-left']['exam']);
                }

                echo '<div class="nav-collapse">';
                $controllerName = $this->view->getControllerName();
                foreach ($this->_headerMenu as $position => $menu) {
                        echo '<ul class="nav ', $position, '">';
                        foreach ($menu as $controller => $option) {
                                if ($controllerName == $controller) {
                                        echo '<li class="active">';
                                } else {
                                        echo '<li>';
                                }
                                echo Phalcon\Tag::linkTo($controller . '/' . $option['action'], $option['caption']);
                                echo '</li>';
                        }
                        echo '</ul>';
                }
                echo '</div>';
        }

}
