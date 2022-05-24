from rest_framework import permissions


class IsAdmin(permissions.BasePermission):
    """
    Permission check for admin
    """

    def has_permission(self, request, view):
        return request.user and request.user.is_superuser


class IsTokenAuthenticated(permissions.BasePermission):
    """
    Permission check for admin
    """

    def has_permission(self, request, view):
        return request.META.get('HTTP_AUTHORIZATION') and request.META.get('HTTP_AUTHORIZATION') == '9944b09199c62bcf9418ad846dd0e4bbdfc6ee4b'
