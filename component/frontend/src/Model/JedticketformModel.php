<?php
/**
 * @package       JED
 *
 * @subpackage    TICKETS
 *
 * @copyright     (C) 2022 Open Source Matters, Inc.  <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Jed\Component\Jed\Site\Model;
// No direct access.
defined('_JEXEC') or die;

use Exception;
use Jed\Component\Jed\Site\Helper\JedHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;

/**
 * JedTicketForm model.
 *
 * @since  4.0.0
 */
class JedticketformModel extends FormModel
{
	/**
	 * The item object
	 *
	 * @var    object
	 * @since  4.0.0
	 */
	private $item = null;

	/**
	 * Data Table
	 * @var string
	 * @since 4.0.0
	 **/
	private string $dbtable = "#__jed_jedtickets";
	/**
	 * Default ticket id
	 * @var int
	 * @since 4.0.0
	 **/
	private int $id = -1;

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $pk  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    4.0.0
	 * @throws Exception
	 */
	public function checkin($pk = null): bool
	{
		// Get the id.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('jedticket.id');
		if (!$pk || JedHelper::userIDItem($pk, $this->dbtable) || JedHelper::isAdminOrSuperUser())
		{
			if ($pk)
			{
				// Initialise the table
				$table = $this->getTable();

				// Attempt to check the row in.
				if (method_exists($table, 'checkin'))
				{
					if (!$table->checkin($pk))
					{
						return false;
					}
				}
			}

			return true;
		}
		else
		{
			throw new Exception(Text::_("JERROR_ALERTNOAUTHOR"), 401);
		}
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   int|null  $pk  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    4.0.0
	 * @throws Exception
	 */
	public function checkout($pk = null): bool
	{
		// Get the user id.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('jedticket.id');
		if (!$pk || JedHelper::userIDItem($pk, $this->dbtable) || JedHelper::isAdminOrSuperUser())
		{
			if ($pk)
			{
				// Initialise the table
				$table = $this->getTable();

				// Get the current user object.
				$user = JedHelper::getUser();

				// Attempt to check the row out.
				if (method_exists($table, 'checkout'))
				{
					if (!$table->checkout($user->get('id'), $pk))
					{
						return false;
					}
				}
			}

			return true;
		}
		else
		{
			throw new Exception(Text::_("JERROR_ALERTNOAUTHOR"), 401);
		}
	}

	/**
	 * Check if data can be saved
	 *
	 * @return bool
	 * @since 4.0.0
	 * @throws Exception
	 */
	public function getCanSave(): bool
	{
		$table = $this->getTable();

		return $table !== false;
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    Form    A Form object on success, false on failure
	 *
	 * @since    4.0.0
	 * @throws Exception
	 */
	public function getForm($data = array(), $loadData = true, $formname = 'jform'): Form
	{
		// Get the form.
		$form = $this->loadForm('com_jed.jedticket', 'jedticketform', array(
				'control'   => $formname,
				'load_data' => $loadData
			)
		);

		if (!is_object($form))
		{
			throw new Exception(Text::_('JERROR_LOADFILE_FAILED'), 500);
		}


		return $form;
	}

	/**
	 *
	 * @return int
	 *
	 * @since version
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * Method to get an object.
	 *
	 * @param   int|null  $id  The id of the object to get.
	 *
	 * @return Object|boolean Object on success, false on failure.
	 *
	 * @since 4.0.0
	 * @throws Exception
	 */
	public function getItem(int $id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('jedticket.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			if ($table !== false && $table->load($id) && !empty($table->id))
			{


				$user = JedHelper::getUser();
				$id   = $table->id;
				if (empty($id) || JedHelper::isAdminOrSuperUser() || $table->created_by == $user->id)
				{


					// Convert the Table to a clean CMSObject.
					$properties = $table->getProperties(1);
					$this->item = ArrayHelper::toObject($properties, CMSObject::class);

					if (isset($this->item->category_id) && is_object($this->item->category_id))
					{
						$this->item->category_id = ArrayHelper::fromObject($this->item->category_id);
					}

				}
				else
				{
					throw new Exception(Text::_("JERROR_ALERTNOAUTHOR"), 401);
				}
			}
		}

		return $this->item;
	}

	/**
	 * Method to delete data
	 *
	 * @param   int  $pk  Item primary key
	 *
	 * @return  int  The id of the deleted item
	 *
	 * @since 4.0.0
	 * @throws Exception
	 *
	 */
	/*public function delete($pk) : int
	{
		$user = JedHelper::getUser();

		if (!$pk || JedHelper::userIDItem($pk, $this->dbtable) || JedHelper::isAdminOrSuperUser())
		{
			if (empty($pk))
			{
				$pk = (int) $this->getState('jedticket.id');
			}

			if ($pk == 0 || $this->getItem($pk) == null)
			{
				throw new Exception(Text::_('COM_JED_ITEM_DOESNT_EXIST'), 404);
			}

			if ($user->authorise('core.delete', 'com_jed') !== true)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			$table = $this->getTable();

			if ($table->delete($pk) !== true)
			{
				throw new Exception(Text::_('JERROR_FAILED'), 501);
			}

			return $pk;
		}
		else
		{
			throw new Exception(Text::_("JERROR_ALERTNOAUTHOR"), 401);
		}
	}*/

	/**
	 * Method to get the table
	 *
	 * @param   string  $name
	 * @param   string  $prefix  Optional prefix for the table class name
	 * @param   array   $options
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 * @since 4.0.0
	 * @throws Exception
	 */
	public function getTable($name = 'Jedticket', $prefix = 'Administrator', $options = array())
	{

		return parent::getTable($name, $prefix, $options);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    array  The default data is an empty array.
	 * @since    4.0.0
	 * @throws Exception
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jed.edit.jedticket.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		if ($data)
		{

			// Support for multiple or not foreign key field: ticket_origin
			$array = array();

			foreach ((array) $data->ticket_origin as $value)
			{
				if (!is_array($value))
				{
					$array[] = $value;
				}
			}
			if (!empty($array))
			{

				$data->ticket_origin = $array;
			}
			// Support for multiple or not foreign key field: ticket_status
			$array = array();

			foreach ((array) $data->ticket_status as $value)
			{
				if (!is_array($value))
				{
					$array[] = $value;
				}
			}
			if (!empty($array))
			{

				$data->ticket_status = $array;
			}

			return $data;
		}

		return array();
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since  4.0.0
	 *
	 * @throws Exception
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request userState on edit or from the passed variable on default
		if (Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_jed.edit.jedticket.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_jed.edit.jedticket.id', $id);
		}

		$this->setState('jedticket.id', $id);

		// Load the parameters.
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('jedticket.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 * @throws Exception
	 */
	public function save(array $data): bool
	{
		$id         = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('jedticket.id');
		$isLoggedIn = JedHelper::IsLoggedIn();


		if (!$id || JedHelper::userIDItem($id, $this->dbtable) || JedHelper::isAdminOrSuperUser() && $isLoggedIn)
		{
			/* Any logged in user can make a new ticket */

			$table = $this->getTable();

			if ($table->save($data) === true)
			{
				$this->id = $table->id;

				return $table->id;
			}
			else
			{
				return false;
			}
		}
		else
		{
			throw new Exception(Text::_("JERROR_ALERTNOAUTHOR"), 401);
		}
	}

}