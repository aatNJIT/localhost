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
    case GET_ALL_USERS = 'getallusers';
    case FOLLOW_USER = 'followuser';
}

class Identifiers
{
    const string USERNAME = 'username';
    const string PASSWORD = 'password';
    const string USER_ID = 'userid';
    const string SESSION_ID = 'sessionid';
    const string STEAM_ID = 'steamid';
    const string STEAM_PROFILE = "profile";
    const string STEAM_GAMES = "games";
    const string LAST_SESSION_CHECK = 'lastsessioncheck';
}