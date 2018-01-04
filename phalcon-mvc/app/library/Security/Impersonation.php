<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// 
// File:    Impersonation.php
// Created: 2015-03-06 13:11:36
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Security;

use Phalcon\Mvc\User\Component;

/**
 * Information about user impersonation.
 * 
 * This class don't actually change user impersonation, it simply represent
 * the current state of user impersonation. The impersonation is stored in
 * session, but might be changed by a user request named impersonate with
 * a username as its value:
 * 
 * <code>
 * ?impersonate=<user>
 * </code>
 * 
 * Only a admin user can impersonate as another user.
 * 
 * @property-read boolean $active Impersonation is enabled for this session.
 * @property-read string $actor The impersonating user (admin).
 * @property-read string $impersonated The impersonated user (user).
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Impersonation extends Component
{

        /**
         * Name of session entry.
         */
        const ENTRY = 'impersonation';

        public function __get($property)
        {
                if ($property == 'active') {
                        return $this->session->has(self::ENTRY);
                } elseif ($property == 'actor') {
                        return $this->session->get(self::ENTRY)['actor'];
                } elseif ($property == 'impersonated') {
                        return $this->session->get(self::ENTRY)['impersonated'];
                } else {
                        return parent::__get($property);
                }
        }

        /**
         * Enable impersonation as user.
         * @param string $user The impersonated user.
         * @return boolean
         */
        public function enable($user)
        {
                if ($this->user->getUser() == null) {
                        $this->logger->auth->alert(sprintf(
                                "Denied impersonate request as %s (not authenticated)", $user
                        ));
                        return false;
                }
                if (!$this->user->roles->acquire(Roles::ADMIN)) {
                        $this->logger->auth->alert(sprintf(
                                "Denied impersonate request as %s (caller is not admin)", $user
                        ));
                        return false;
                }

                $this->session->set(self::ENTRY, array(
                        'actor'        => $this->user->getPrincipalName(),
                        'impersonated' => $user
                ));
                $this->logger->auth->notice(sprintf(
                        "Enabled impersonation as %s for %s from %s", $this->impersonated, $this->actor, $this->request->getClientAddress(true)
                ));

                return true;
        }

        /**
         * Break current impersonation.
         */
        public function disable()
        {
                $this->logger->auth->notice(sprintf(
                        "Disabled impersonation as %s for %s from %s", $this->impersonated, $this->actor, $this->request->getClientAddress(true)
                ));
                $this->session->remove(self::ENTRY);
        }

}
