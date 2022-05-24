from rest_framework.pagination import PageNumberPagination as DefaultPageNumberPagination


class PageNumberPagination(DefaultPageNumberPagination):
    """
    Override DefaultPageNumberPagination for disabling pagination from QUERY PARAM
    """

    def __init__(self):
        super().__init__()

    def paginate_queryset(self, queryset, request, view=None):

        self.__set_page_size(request.query_params)

        if any(['show_all' in request.query_params, 'show_all/' in request.query_params]):
            super().paginate_queryset(queryset, request, view)
            return list(queryset)

        return super().paginate_queryset(queryset, request, view)

    def __set_page_size(self, query_params):
        """
        Try to get page_size parameter from query params if it exists.
        If something goes wrong the don't changing anything.
        :param query_params:
        :return: None
        """

        try:
            page_size = int(query_params.get('page_size', ''))
        except ValueError:
            return

        if page_size > 0:
            self.page_size = page_size
