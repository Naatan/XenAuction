INDEX

 - [1] Installation

 - [2] Customization

   - Extra Menu Links
   - Notification Signatures

 - [3] Configuration Help

   - User ID that sends notifications
   - Privacy Mode
   - Enable Random Auctions Widget
   - Widget Placement
   - Widget Display Mode
   - Widget Display Criteria

 - [4] User Preferences Help

   - Default Auction Confirmation Message
   - Auction Payment Address


[1] Installation
================

1. Extract the contents of the data, js and library folders to your XenForo install folders of the same name.
2. Install the addon in XenForo by going into the admin panel, then to the "Install Addon" section and then by uploading the "addon-xenauction.xml" file included with this addon.
3. Go to the XenForo options page and open up the "Auctions" group. Configure the options as you see fit. For help on individual options see the "Configuration Help" section below.
4. In the admin panel, click the Users tab and then choose "Group Permissions". Edit the permissions for the groups that should have some form of interaction with the Auctions.
5. Before creating your first auction, go to your User Profile and click on Preferences, there will be some new auction related settings near the bottom of this screen; configure them as needed (see the "User Preferences Help" section below for assistance).
6. Done


[2] Customization
=================

  Extra Menu Links
  ----------------
  
  You can add additional sub-menu links by editing the template "auction_navigation_tab_extra"
  
  An example entry would be:
  
    <li><a href="{xen:link help/auctions}">{xen:phrase help}</a></li>
  
  This template is there for the specific purpose of allowing you to define additional menu entries, so you do not have to worry about incompatibilities about conflicts with future versions.
  
  Notification Signatures
  -----------------------
  
  You can add a signature to notifications by editing the phrase "auction_notification_signature".
  
  This is useful if you want to instruct users on general usage / rules when purchasing auctions. For example you could tell them "not to respond to this message and address any concerns to user xxx".


[3] Configuration Help
======================

  "User ID that sends notifications" 
  ----------------------------------
  
  This user will be used to send notification messages to users when relevant (eg. user won an auction, user was outbid, etc).
  
  It is best you configure a brand new user for this task (eg. "Auction Notifier"). Using the configured user to buy / sell auctions will cause errors.
  
  "Privacy Mode"
  --------------
  
  With privacy mode turned on auctions will not show bidder information, keeping buyers anonymous for the public.
  
  "Enable Random Auctions Widget"
  -------------------------------
  
  The random auctions widget shows a selection of random auctions (as the name implies) in a horizontal order, using the auction thumbnail, it's name and it's current price. When clicked the user can view the auction details and choose to bid on it / buy it immediately, depending on the auction.
  
  "Widget Placement"
  ------------------
  
  Widget placement lets you pick where the auction widget should be embedded. Note that this simply hooks into locations in your templates that XenForo makes available. Not all of these hooks will render a proper result, it all depends on your own theme.
  
  "Widget Display Mode"
  ---------------------
  
  You can choose not to show the widget on certain pages, or only show them on specified pages. 
  
  When whitelist mode is enabled, the auction widget will only be shown on pages you specify. 
  
  With blacklist mode, the auction widget is displayed on all pages except the ones specified.
  
  "Widget Display Criteria"
  -------------------------
  
  Here you can specify the pages that should be whitelisted or blacklisted (based on the display mode you configured).
  
  The configuration format allows you to specify a prefix, and optionally an action, for example;
  
   - setting: forums|create-thread 
   - matches: http://xenforo/forums/main-forum.2/create-thread
  
   - setting: auctions
   - matches: http://xenforo/auctions
  
  Ensure each rule is placed on a new line.


[4] User Preferences Help
=========================

  "Default Auction Confirmation Message"
  --------------------------------------
  
  When a sale was made you will be able to mark it "Complete" when the payment has been received and you have delivered the item to the buyer. This setting will allow you to configure a default message to be send to the buyer upon completion. When completing a sale the message field will be pre-populated with this setting, but you will still be able to change it.
  
  You can use the following tags:
  
   - {username}	: username of the buyer
   - {link}	: link to the relevant auction
   - {title} 	: title of the relevant auction
   - {amount}	: the amount the buyer paid
  
  "Auction Payment Address"
  -------------------------
  
  The address where the buyer should send his payment to. This can be a mailing address but you could also simply provide instructions like "send your payment to this paypal address: xxx".