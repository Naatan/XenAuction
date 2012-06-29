<?php

class XenAuction_Helper_Notification
{

	public static function sendNotification($userId, XenForo_Phrase $title, XenForo_Phrase $message) 
	{
			$options 	= XenForo_Application::get('options');
		
			$userModel 	= XenForo_Model::create('XenForo_Model_User');
			$sender 	= $userModel->getUserById($options->auctionNotificationSender);

			$title 		= $title->render();
			$message 	= $message->render();

			$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
			$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $sender);
			$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $message);
			$conversationDw->set('user_id', $sender['user_id']);
			$conversationDw->set('username', $sender['username']);
			$conversationDw->set('title', $title);
			$conversationDw->set('open_invite', 0);
			$conversationDw->set('conversation_open', 0);
			$conversationDw->addRecipientUserIds(array($userId)); // checks permissions

			$messageDw = $conversationDw->getFirstMessageDw();
			$messageDw->set('message', $message);

			$conversationDw->save();

			$conversation = $conversationDw->getMergedData();

			$db = XenForo_Application::getDb();

			$conversationModel = XenForo_Model::create('XenForo_Model_Conversation');
			$conversationModel->deleteConversationForUser($conversation['conversation_id'], $sender['user_id'], 'deleted');
			$db->delete('xf_conversation_recipient',
				'conversation_id = ' . $conversation['conversation_id'] . ' AND user_id = ' . $sender['user_id']
			);
	}

}