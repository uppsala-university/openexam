<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Translate.php
// Created: 2014-09-19 12:21:54
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Globalization\Translate\Gettext;

use OpenExam\Library\Globalization\Exception;
use OpenExam\Library\Globalization\Translate\Translate as TranslateInterface;
use Phalcon\Mvc\User\Component;
use Phalcon\Translate\Adapter\Gettext;

/**
 * Provides translation service using GNU gettext.
 * 
 * This is how to make a translation object accessible in a view:
 * 
 * <code>
 * // Controller:
 * $this->view->setVar("user", $this->user->name);
 * $this->view->setVar("tr", new Translate('admin'));
 * 
 * // View:
 * $tr->_("Welcome %s", array($user));
 * </code>
 * 
 * For core application (models, controllers, components etc) it could be
 * useful to define a core translation service in DI:
 * 
 * <code>
 * // services.php:
 * $di->set('tr', function() use($di) {
 *      return new Translate('core');
 * });
 * </code>
 * 
 * Message catalogs are usually stored in a locale directory organized by
 * the locale. The *.po is the message catalog and the *.mo are the binary
 * format (compiled) actually used by gettext.
 * 
 * locale/
 *    +-- en_US/
 *           +-- LC_MESSAGES/
 *                  +-- messages.po
 *                  +-- messages.mo
 *    +-- sv_SE.UTF-8/
 *           +-- LC_MESSAGES/
 *                  +-- messages.po
 *                  +-- messages.mo
 *    +-- messages.pot
 * 
 * The *.pot file is the message catalog template from where new translation
 * files (messages.po) can be generated. The Setup class (declared in this 
 * namespace) can be used for initialize, update, merge and compile message 
 * catalogs.
 * 
 * Message catalog modules are defined in app/config/system/config.php (the 
 * translate sub-tree). See docs/develop/gettext.txt for more information.
 * 
 * Notice:
 * --------
 * 
 * Use _(), gettext() or ngettext() for marking up strings for translation
 * in sources. If using query() (the Phalcon proposed standard), then the
 * source code scanning of translation strings (using xgettext) will not find
 * them or if adding query to --keywords, we will probably get SQL in the
 * translation templates (*.pot-file).
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Translate extends Component implements TranslateInterface
{

        /**
         * Using version from incubator.
         * @var bool 
         */
        private $incubator = false;
        /**
         * @var Gettext
         */
        private $gettext;

        /**
         * Constructor.
         * @param string|array $domain The text domain.
         */
        public function __construct($domain = 'messages')
        {
                if (!extension_loaded('gettext')) {
                        throw new Exception('The gettext extension is not loaded.');
                }

                if ($this->incubator == false) {
                        $this->gettext = new Gettext(array(
                                'locale'        => $this->locale->getLocale(),
                                'defaultDomain' => $domain,
                                'directory'     => $this->config->application->localeDir
                        ));
                } elseif (is_string($domain)) {
                        $this->gettext = new Gettext(array(
                                'locale'    => $this->locale->getLocale(),
                                'file'      => $domain,
                                'directory' => $this->config->application->localeDir
                        ));
                } else {
                        $this->gettext = new Gettext(array(
                                'locale'  => $this->locale->getLocale(),
                                'domains' => $domain
                        ));
                }
        }

        public function exists($index)
        {
                return $this->gettext->exists($index);
        }

        public function query($index, $placeholders = null)
        {
                return $this->gettext->query($index, $placeholders);
        }

        public function _($msgid, $params = null)
        {
                return $this->gettext->_($msgid, $params);
        }

        public function gettext($msgid, $params = null)
        {
                return $this->gettext->_($msgid, $params);
        }

        public function ngettext($msgid1, $msgid2, $count, $params = null)
        {
                return $this->gettext->nquery($msgid1, $msgid2, $count, $params);
        }

}
