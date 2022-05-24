<?php

/**
 * @OA\Get(
 *      path="/api/v1/user",
 *      tags={"User"},
 *      summary="User Listing",
 *      description="Get User Listing",
 *      @OA\Parameter(name="withoutPagination", required=false, in="query", @OA\Schema(type="integer")),
 *      @OA\Parameter(name="favourites", required=false, in="query", @OA\Schema(type="integer")),
 *      @OA\Parameter(name="connections", required=false, in="query", @OA\Schema(type="integer")),
 *      @OA\Parameter(name="pending_connections", required=false, in="query", @OA\Schema(type="integer")),
 *      @OA\Parameter(name="without_connections", required=false, in="query", @OA\Schema(type="integer")),
 *      @OA\Parameter(name="search", required=false, in="query", @OA\Schema(type="string")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/UserListing")),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Get(
 *      path="/api/v1/me",
 *      tags={"User"},
 *      summary="User Info",
 *      description="Get Auth User",
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/UserMe")),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Get(
 *      path="/api/v1/user/additional-data",
 *      tags={"User"},
 *      summary="User Connections and  Data",
 *      description="User Connections and Data",
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent()),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Put(
 *      path="/api/v1/user/{user_id}",
 *      tags={"User"},
 *      summary="User update",
 *      description="User update",
 *      @OA\Parameter(name="user_id", required=true, in="path", @OA\Schema(type="integer")),
 *      @OA\Parameter(name="first_name", required=false, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="last_name", required=false, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="email", required=false, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="bio", required=false, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="avatar", required=false, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="phone", required=false, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="is_private", required=false, in="query", @OA\Schema(type="integer")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/UserSimple")),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Post(
 *      path="/api/v1/user/add",
 *      tags={"User"},
 *      summary="User add to friendship",
 *      description="User add to friendship",
 *      @OA\Parameter(name="email", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent()),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Post(
 *      path="/api/v1/user/add-to-favourites",
 *      tags={"User"},
 *      summary="User add to favorite contacts",
 *      description="User add to favorite contacts",
 *      @OA\Parameter(name="user_id", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent()),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Post(
 *      path="/api/v1/user/remove-from-favourites",
 *      tags={"User"},
 *      summary="User remove from favorite contacts",
 *      description="User remove from favorite contacts",
 *      @OA\Parameter(name="user_id", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent()),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Post(
 *      path="/api/v1/change-password",
 *      tags={"User"},
 *      summary="User change password",
 *      description="User update",
 *      @OA\Parameter(name="old_password", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="password", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Parameter(name="password_confirmation", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent())),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Post(
 *      path="/api/v1/user/add-to-blocked",
 *      tags={"User"},
 *      summary="User add to block list",
 *      description="User add to favorite contacts",
 *      @OA\Parameter(name="user_id", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent()),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Post(
 *      path="/api/v1/user/remove-from-blocked",
 *      tags={"User"},
 *      summary="User remove from blocked list",
 *      description="User remove from favorite contacts",
 *      @OA\Parameter(name="user_id", required=true, in="query", @OA\Schema(type="string")),
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent()),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */

/**
 * @OA\Get(
 *      path="/api/v1/user/blocked-users-list",
 *      tags={"User"},
 *      summary="Blocked Users Listing",
 *      description="Get Blocked Users Listing",
 *      @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/UserListing")),
 *      @OA\Response(response=401, description="Unauthenticated"),
 *      @OA\Response(response=403, description="Forbidden")
 *     )
 */
