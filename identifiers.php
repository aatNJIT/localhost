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
    case PROFILE = 'profile';
    case SAVE_CATALOG = 'savecatalog';
    case GET_USER_CATALOGS = 'getusercatalogs';
    case GET_ALL_CATALOGS = 'getallcatalogs';
    case GET_CATALOG = 'getcatalog';
    case GET_ALL_USERS = 'getallusers';
    case FOLLOW_USER = 'followuser';
    case GET_USER_FOLLOWERS = 'getuserfollowers';
    case UNFOLLOW_USER = 'unfollowuser';
    case ADD_CATALOG_COMMENT = 'addcatalogcomment';
    case STORE_USER_GAME = 'storeusergame';
    case GET_USER_GAMES = 'getusergames';
    case GET_TAGS = 'tags';
    case STORE_GAME_TAGS = 'storegametags';
    case GET_USER_FOLLOWING = 'getuserfollowing';
    case GET_CATALOG_COMMENTS = 'getcatalogcomments';
    case GET_USER = 'getuser';
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
    const string STEAM_GAMES = "games";
    const string LAST_SESSION_CHECK = 'lastsessioncheck';
    const string LAST_GAME_SESSION_CHECK = 'lastgamesessioncheck';
}