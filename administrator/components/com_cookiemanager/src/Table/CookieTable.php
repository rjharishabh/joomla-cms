<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_cookiemanager
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Cookiemanager\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

/**
 * Group Table class.
 *
 * @since   __DEPLOY_VERSION__
 */
class CookieTable extends Table
{

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  Database connector object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__cookiemanager_cookies', 'id', $db);
	}

	/**
	 * Stores cookies.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function store($updateNulls = true)
	{
		$date   = Factory::getDate()->toSql();
		$userId = Factory::getUser()->id;

		// Set created date if not set.
		if (!(int) $this->created)
		{
			$this->created = $date;
		}

		if ($this->id)
		{
			$this->modified_by = $userId;
			$this->modified    = $date;
		}
		else
		{
			if (empty($this->created_by))
			{
				$this->created_by = $userId;
			}

			if (!(int) $this->modified)
			{
				$this->modified = $date;
			}

			if (empty($this->modified_by))
			{
				$this->modified_by = $userId;
			}
		}

		// Verify that the alias is unique
		$table = Table::getInstance('CookieTable', __NAMESPACE__ . '\\', array('dbo' => $this->getDbo()));

		if ($table->load(array('alias' => $this->alias, 'catid' => $this->catid)) && ($table->id != $this->id || $this->id == 0))
		{
			$this->setError(Text::_('COM_COOOKIEMANAGER_ERROR_UNIQUE_ALIAS'));

			return false;
		}

		return parent::store($updateNulls);
	}

	/**
	 * Overloaded check function
	 *
	 * @return  boolean  True on success, false on failure
	 *
	 * @see     \JTable::check
	 * @since   1.5
	 */
	public function check()
	{
		try
		{
			parent::check();
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		// Check for valid title
		if (trim($this->title) == '')
		{
			$this->setError(Text::_('COM_COOKIEMANAGER_WARNING_PROVIDE_VALID_TITLE'));

			return false;
		}

		// Generate a valid alias
		$this->generateAlias();

		// Check for a valid category.
		if (!$this->catid = (int) $this->catid)
		{
			$this->setError(Text::_('JLIB_DATABASE_ERROR_CATEGORY_REQUIRED'));

			return false;
		}

		// Sanity check for user_id
		if (!$this->user_id)
		{
			$this->user_id = 0;
		}

		return true;
	}

	/**
	 * Generate a valid alias from title / date.
	 * Remains public to be able to check for duplicated alias before saving
	 *
	 * @return  string
	 */
	public function generateAlias()
	{
		if (empty($this->alias))
		{
			$this->alias = $this->title;
		}

		$this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
		}

		return $this->alias;
	}
}
