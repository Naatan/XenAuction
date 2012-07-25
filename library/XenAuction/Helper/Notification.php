<?php

/**
 * Helper for sending notifications to users
 *
 * Currently hooks into the conversations system to send notifications
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_Helper_Notification
{

	/**
	 * Send a notification to the given user
	 * 
	 * @param int        				$userId  
	 * @param XenForo_Phrase 			$title   
	 * @param string|XenForo_Phrase    	$message 
	 * @param array|bool        		$sender  
	 * 
	 * @return void
	 */
	public static function sendNotification($userId, XenForo_Phrase $title, $message, $sender = false) 
	{
		$options 	= XenForo_Application::get('options');
		
		// If sender is not defined, use the default configured one
		if ( ! $sender)
		{
			$userModel 	= XenForo_Model::create('XenForo_Model_User');
			$sender 	= $userModel->getUserById($options->auctionNotificationSender);
			$delete 	= true;
		}
		else
		{
			$delete = false; // Don't delete the conversation for the sender
		}
		
		// Prepare title and message
		$title 		= $title->render();
		$signature 	= new XenForo_Phrase('auction_notification_signature');
		
		if ($message instanceof XenForo_Phrase)
		{
			$message = $message->render();
		}
		
		// Append signature to message
		$message .= $signature->render();
		
		// Preserve & symbol in message (for links)
		$message = str_replace('&amp;', '&', $message);
		
		// Prepare conversation datawriter
		$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
		$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $sender);
		$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $message);
		
		// Set conversation data
		$conversationDw->set('user_id', $sender['user_id']);
		$conversationDw->set('username', $sender['username']);
		$conversationDw->set('title', $title);
		$conversationDw->set('open_invite', 0);
		$conversationDw->set('conversation_open', 0);
		
		// Add recipients to conversation
		$conversationDw->addRecipientUserIds(array($userId)); // checks permissions
		
		// Add message to conversation
		$messageDw = $conversationDw->getFirstMessageDw();
		$messageDw->set('message', $message);
		
		// Save the conversation
		$conversationDw->save();
		
		// If delete is set, delete the conversation from the sender
		// so his conversation list doesn't get polluted
		if ($delete)
		{
			// Get conversation data
			$conversation = $conversationDw->getMergedData();
			
			// Delete conversation for the sender
			$conversationModel = XenForo_Model::create('XenForo_Model_Conversation');
			$conversationModel->deleteConversationForUser($conversation['conversation_id'], $sender['user_id'], 'deleted');
			
			// Delete recipient entry
			$db = XenForo_Application::getDb();
			$db->delete('xf_conversation_recipient',
				'conversation_id = ' . $conversation['conversation_id'] . ' AND user_id = ' . $sender['user_id']
			);
		}
	}

}