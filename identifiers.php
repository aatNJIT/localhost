<?php

enum RequestType: string
{
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case REGISTER = 'register';
    case SESSION = 'session';
    case LINK = 'link';
    case UNLINK = 'unlink';
    case GAMES = 'games';
    case SEARCH_GAMES = 'searchgames';
    case GET_GAMES_INFO = 'getinfoforgames';
    case PROFILE = 'profile';
    case SAVE_CATALOG = 'savecatalog';
    case GET_USER_CATALOGS = 'getusercatalogs';
    case GET_ALL_CATALOGS = 'getallcatalogs';
    case GET_CATALOG = 'getcatalog';
    case GET_ALL_USERS = 'getallusers';
    case FOLLOW_USER = 'followuser';
    case GET_USER_GAMES = 'getusergames';
    case GET_USER_FOLLOWERS = 'getuserfollowers';
    case UNFOLLOW_USER = 'unfollowuser';
    case ADD_CATALOG_COMMENT = 'addcatalogcomment';
    case GET_TAGS = 'tags';
    case STORE_GAME_TAGS = 'storegametags';
    case GET_USER_FOLLOWING = 'getuserfollowing';
    case GET_CATALOG_COMMENTS = 'getcatalogcomments';
    case SUBMIT_COMMENT = 'submitcomment';
    case GET_USER = 'getuser';
    case TWO_FA_LOGIN = '2fa_login';
    case TWO_FA_VERIFY = '2fa_verify';
}

class Identifiers
{
    const string USERNAME = 'username';
    const string PASSWORD = 'password';
    const string USER_ID = 'userid';
    const string CATALOG_ID = 'catalogid';
    const string FOLLOW_ID = 'followid';
    const string SESSION_ID = 'sessionid';
    const string STEAM_ID = 'steamid';
    const string STEAM_PROFILE = "profile";
    const string OTP = 'otp';
}