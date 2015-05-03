<?php /*Initial Data*/
define("DOG_LEVEL", 1);
define("USR_FIRST_BALANCE", 500);
define("USR_CHNG_DOG", 1000);
define("INVITER_BONUS", 100);
define("USERS_EVERYDAY_BONUS", 50);

/*FOR Database*/

define("MSG_DB_NOT_CONNECT", "cannot connect to database ");

/*FOR Users actions*/

define("MSG_NOT_CORRECT_USER_ID", "invalid user ID ");
define("MSG_NO_USER_ID", "Specified user does not exist!");
define("MSG_NO_USER_INFORMATION", "could Not retrieve information about the dog and its owner. ");
define("MSG_NO_OWNER_INFORMATION", "Info there is no ");
define("MSG_WRONG_CODE", "is Not a correct verification code!! ");
define("MSG_WRONG_AMOUNT", "Not the correct number of items!! ");
define("MSG_WRONG_REQUEST_PARAM", "Incorrect parameters of the query! ");
define("MSG_WRONG_ITEM", "No items to move! ");
define("MSG_WRONG_STEROID_ITEM", "The object does not exist, or you have entered an invalid slot!");
define("MSG_WRONG_BUY_ITEM", "The object does not exist! ");
define("MSG_WRONG_ITEM_AMOUNT", "You do not HAVE the required number of items");
define("MSG_MOVE_ITEM_ERROR", "When you move the object an error occurred! Please try again");
define("MSG_MOVE_ITEM_OK", "Object moved successfully");
define("MSG_WRONG_ITEM_SLOT", "Invalid slot subject!");
define("MSG_ITEM_SLOT_ALREADY_USED","This slot is already occupied!");
define("MSG_NO_ITEM_SLOT", "You have run out OF free cells for the purchase of new items!");
define("MSG_BUY_ITEM_ERR", "When buying an error occurred! Try again.");
define("MSG_BUY_ITEM_OK", "Item successfully purchased and to be in Your booth");
define("MSG_SELL_ITEM_ERR", "When selling an error occurred! Try again.");
define("MSG_SELL_ITEM_OK", "Item successfully sold");

define("MSG_WRONG_BREED", "Breed of dog is incorrect!! ");
define("MSG_NO_DOG_NAME", "the dog's Name cannot be empty ");
define("MSG_DOG_ID_WRONG_TYPE", "ID dog must be an integer value ");
define("MSG_USER_ID_WRONG_TYPE", "user ID must be an integer value ");
define("MSG_USER_IS_BANNED", "Your account is temporarily suspended for not following the rules! The lock expires ");
define("MSG_USER_IS_BANNED_DAYS", " days ");
define("MSG_DOG_BREED_CHNG_ERR", "to change the breed of the dog there's an error! Please try again");
define("MSG_DOG_BREED_NAME_CHNG", "Breed of dog and name were changed successfully");

define("MSG_PROBLEM_WITH_SQL", " Sorry, an error occurred while processing query! Try again. ");

define("ERR_DOG_NOT_CREATED", "Error: the Dog was not created, try again ");
define("ERR_WRONG_USER", "Error: wrong username!! ");
define("ERR_WRONG_PWD", "Error: incorrect password!! ");
define("ERR_WRONG_HASH", "Error: incorrect verification code!! ");

define("TXT_METHOD", "Method ");
define("TXT_NOT_SUPPORTED", " not supported ");

define("TXT_API_SECRET", "PIBhkiPrKe");
define("TXT_SECURE_CODE", "bmAifNl0Ag3tSjQkcy4W");

/*FOR Shop actions*/
define("MSG_NOT_ENOUGH_MONEY", "You don't HAVE enough coins to bring this subject!");
define("MSG_NOT_ENOUGH_MONEY_CHNG", "You don't HAVE enough coins to change Your dog!");
define("MSG_BUY_FIGHT_OK", "Additional fight successfully purchased!");
define("MSG_NOT_ENOUGH_MONEY_BUY_F","You HAVE enough money to purchasing additional combat!");

/*FOR Training actions*/
define("MSG_NOT_ENOUGH_MONEY_TRAIN","You don't HAVE enough chips to start training!");
define("MSG_DOG_TRAIN_NOT_YET", "Your dog is already in training. Wait for the end of the lessons. You can start a new workout through ");
define("MSG_DOG_NOW_TRAINS", "Your dog to be in training camp. You can continue fighting through ");
define("MSG_NOT_ENOUGH_MONEY_TRAIN","You don't HAVE enough chips to start training!");
define("MSG_DOG_TRAIN_START_OK", "Your dog has successfully launched the training session");
define("MSG_WRONG_BUY_TRAIN", "This training does not exist! ");

/*FOR Fight actions*/

define("TXT_DOG_WIN_FIGHT", "a Dog won the match ");
define("TXT_DOG_LOSE_FIGHT", "the Dog fell as martyrs ");
define("TXT_BEFORE_FIGHT", " You should wait for completion of fighting arenas. The arena will be ready on January 20, 2010. During this time, your dog will grow up and will be ready for fights "); ?>