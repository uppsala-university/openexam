<?php

use Phalcon\Tag as Tag;

/**
 * Utility controller
 * Handling Authentication related stuff.
 * 
 */
class AuthController extends ControllerBase
{

        public function initialize()
        {
                parent::initialize();
        }

        /**
         * Register authenticated user into session data
         * Authentication service will call this function.
         * 
         * @param Users $user
         */
        private function _registerSession($user)
        {
                $this->session->set('auth', array(
                        'id'   => $user->id,
                        'name' => $user->name
                ));
        }

        /**
         * Action that is called after authentication process gets completed. 
         * 
         * If success, 
         */
        public function startAction()
        {
                if ($this->session->has('auth')) {
                        $this->flash->success('Welcome ' . $user->name);
                        return $this->forward('exam/index');
                }

                $this->flash->error('Wrong credentials provided');
                return $this->forward('index/index');
        }

        /**
         * Logs out the active session redirecting to the index
         *
         * @return unknown
         */
        public function endAction()
        {
                $this->session->remove('auth');
                $this->flash->success('Goodbye!');
                return $this->forward('index/index');
        }

}
