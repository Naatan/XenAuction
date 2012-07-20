<?php

class XenAuction_Model_UserField extends XFCP_XenAuction_Model_UserField
{

	/**
	 * Gets the user field value for the given field and user.
	 *
	 * @param string $fieldId
	 * @param integer $userId
	 *
	 * @return string|array value 
	 */
	public function getUserFieldValue($fieldId, $userId)
	{
		$field = $this->_getDb()->fetchRow('
			SELECT value.*, field.field_type
			FROM xf_user_field_value AS value
			INNER JOIN xf_user_field AS field ON (field.field_id = value.field_id)
			WHERE value.user_id = ? AND value.field_id = ?
		', array($userId, $fieldId));

		if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect')
		{
			return @unserialize($field['field_value']);
		}
		else
		{
			return $field['field_value'];
		}
	}

}