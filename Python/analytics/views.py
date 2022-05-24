import time

from django.contrib.postgres.fields.jsonb import KeyTextTransform
from django.db.models import Count, DateField, Avg, IntegerField, Q, Sum
from django.db.models.functions import Trunc, Cast, TruncTime, TruncDate
from rest_framework import status
from rest_framework.response import Response
from rest_framework.views import APIView

from apps.aggregations.models import RegisteredCustomers, PopupData, PopupRevenue, VoucherViews, VoucherOrders, TransactionItems, RecommendationData, RecommendationRevenue
from apps.analytics.models import TrackSession, TrackData
from apps.analytics.serializers import TrackDataSerializer, LastActionByTypeSerializer, AutocompleteRegisteredUsersSerializer, SessionProductsAnalyzeSerializer, AnalyticDataByTypeSerializer, VouchersSerializer, MergeCustomersSerializer, UserVouchersSerializer, LastSessionMostPopularSerializer, UserVouchersTimeSerializer, PopupSerializer, RecommendationSerializer


class TrackAnalyticData(APIView):
    # permission_classes = [IsAuthenticated, ]
    serializer_class = TrackDataSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():
            session, created = TrackSession.objects.update_or_create(
                shop_id=serializer.data['shop_id'],
                customer_id=serializer.data['customer_id'],
                session_id=serializer.data['session_id'],
            )

            if serializer.data['type'] == TrackData.types['register']:
                last_action = TrackData.objects.filter(
                    session__session_id=serializer.data['session_id'],
                    type=TrackData.types['sign']
                ).order_by('-created_at').values('created_at').first()
                seconds_diff = round(time.time() - last_action['created_at'].timestamp())
                serializer.data['data'].update({'duration': seconds_diff})

            # track time customer spent on product
            get_types = {
                'other': 0,
                'product': 1,
                'category': 2,
            }

            compare_types = {
                'other': 0,
                'product': 1,
                'category': 2,
            }

            # Turn list of values into list of Q objects
            queries = [Q(type=type_id) for get_type, type_id in get_types.items()]
            query = queries.pop()
            for item in queries:
                query |= item

            if serializer.data['type'] in compare_types.values():
                last_action = TrackData.objects.filter(
                    session__session_id=serializer.data['session_id']
                ).filter(query).order_by('-created_at').first()

                if last_action and 'view_time' not in last_action.data:
                    seconds_diff = round(time.time() - last_action.created_at.timestamp())
                    last_action.data.update({'view_time': seconds_diff})
                    last_action.save()

            track_data = TrackData.objects.create(
                session=session,
                url=serializer.data['url'],
                uuid=serializer.data['uuid'],
                type=serializer.data['type'],
                data=serializer.data['data']
            )

            return Response({'success': True, 'track_id': track_data.pk}, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class MergeCustomers(APIView):
    # permission_classes = [IsAuthenticated, ]
    serializer_class = MergeCustomersSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():
            TrackData.objects.filter(
                session__session_id=serializer.data['slave_id'],
                type=TrackData.types['first_viewed_page']
            ).delete()

            TrackSession.objects.filter(
                session_id=serializer.data['slave_id']
            ).update(
                session_id=serializer.data['master_id']
            )

            return Response({'success': True}, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class LastActionByType(APIView):
    # permission_classes = [IsAuthenticated, ]
    serializer_class = LastActionByTypeSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            last_session = TrackSession.objects.filter(
                shop_id=serializer.data['shop_id'],
                customer_id=serializer.data['customer_id']
            )

            if serializer.data.get('session_id', None):
                last_session = last_session.exclude(
                    session_id=serializer.data['session_id']
                )

            last_session = last_session.order_by('-created_at').first()

            if last_session is None:
                return Response({}, status=status.HTTP_200_OK)

            analytic_query = TrackData.objects.filter(
                session=last_session
            )

            if serializer.data.get('alt_type', None):
                analytic_query = analytic_query.filter(
                    Q(type=serializer.data['type']) | Q(type=serializer.data['alt_type'])
                )
            else:
                analytic_query = analytic_query.filter(
                    type=serializer.data['type']
                )

            analytic = analytic_query.values('data').order_by('-created_at').first()

            if analytic is None:
                return Response({}, status=status.HTTP_200_OK)

            return Response(analytic['data'], status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class LastSessionMostPopular(APIView):
    # permission_classes = [IsAuthenticated, ]
    serializer_class = LastSessionMostPopularSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            last_product = TrackData.objects.filter(
                session__shop_id=serializer.data['shop_id'],
                session__customer_id=serializer.data['customer_id'],
                type=TrackData.types['product']
            )

            if serializer.data.get('session_id', None):
                last_product = last_product.exclude(
                    session__session_id=serializer.data['session_id']
                )

            if serializer.data.get('exclude_id', None):
                last_product = last_product.exclude(
                    data__articleID=serializer.data['exclude_id']
                )

            last_product = last_product.order_by('-created_at').first()

            if last_product is None:
                return Response({}, status=status.HTTP_200_OK)

            last_session = last_product.session

            analytic_query = TrackData.objects.filter(
                session=last_session,
                type=TrackData.types['product']
            )

            if serializer.data.get('exclude_id', None):
                analytic_query = analytic_query.exclude(
                    data__articleID=serializer.data['exclude_id']
                )

            analytic = analytic_query.annotate(view_time=Cast(KeyTextTransform('view_time', 'data'), IntegerField(default=0))) \
                .filter(view_time__isnull=False) \
                .values('data').order_by('-view_time').first()

            if analytic is None:
                analytic = analytic_query.values('data').order_by('-created_at').first()

            if analytic is None:
                return Response({}, status=status.HTTP_200_OK)

            return Response(analytic['data'], status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)



class AutocompleteRegisteredUsersTimeSpent(APIView):
    permission_classes = [IsTokenAuthenticated, ]
    serializer_class = AutocompleteRegisteredUsersSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            registered_users = RegisteredCustomers.objects.filter(
                shop_id=serializer.data['shop_id'],
                duration__isnull=False
            registered_users_with_autofill = registered_users.filter(with_autofill=True)
            get_avg_with = registered_users_with_autofill \
                .aggregate(avg_time=Avg('duration'))

            if get_avg_with['avg_time']:
                registered_users_with_autofill = registered_users_with_autofill.filter(duration__lte=2 * get_avg_with['avg_time'])

            data_with_autofill = registered_users_with_autofill \
                .aggregate(avg_time=Avg('duration'))

            registered_users_without_autofill = registered_users.filter(with_autofill=False)
            get_avg_without = registered_users_without_autofill \
                .aggregate(avg_time=Avg('duration'))

            if get_avg_without['avg_time']:
                registered_users_without_autofill = registered_users_without_autofill.filter(duration__lte=2 * get_avg_without['avg_time'])

            data_without_autofill = registered_users_without_autofill \
                .aggregate(avg_time=Avg('duration'))

            data = {
                'time_with_autofill': round(data_with_autofill['avg_time']) if data_with_autofill['avg_time'] else 0,
                'time_without_autofill': round(data_without_autofill['avg_time']) if data_without_autofill['avg_time'] else 0,
            }

            return Response(data, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class PopUpShowVsClick(APIView):
    permission_classes = [IsTokenAuthenticated, ]
    serializer_class = PopupSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            popup_data = PopupData.objects.filter(
                shop_id=serializer.data['shop_id'],
            )

            if serializer.data['start_date']:
                popup_data = popup_data.filter(created_at__date__gte=serializer.data['start_date'])

            if serializer.data['end_date']:
                popup_data = popup_data.filter(created_at__date__lte=serializer.data['end_date'])

            group_type = 'month'
            if serializer.data['group_type']:
                group_type = serializer.data['group_type']

            if group_type == 'hour':
                popup_data = popup_data.annotate(date=TruncTime('created_at'))
            elif group_type == 'day':
                popup_data = popup_data.annotate(date=TruncDate('created_at'))
            else:
                popup_data = popup_data.annotate(date=Trunc('created_at', group_type, output_field=DateField()))

            number_shows = popup_data \
                .values('date') \
                .filter(type=TrackData.types['_show']) \
                .annotate(count=Sum('data_count')) \
                .values('date', 'count')

            number_clicks = popup_data \
                .values('date') \
                .filter(type=TrackData.types['_click']) \
                .annotate(count=Sum('data_count')) \
                .values('date', 'count')

            return Response({
                'number_shows': number_shows,
                'number_clicks': number_clicks
            }, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class PopUpClickVsPurchase(APIView):
    permission_classes = [IsTokenAuthenticated, ]
    serializer_class = PopupSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            popup_data = PopupData.objects.filter(
                shop_id=serializer.data['shop_id'],
            )

            popup_revenue = PopupRevenue.objects.filter(
                shop_id=serializer.data['shop_id'],
            )

            if serializer.data['start_date']:
                popup_data = popup_data.filter(created_at__date__gte=serializer.data['start_date'])
                popup_revenue = popup_revenue.filter(created_at__date__gte=serializer.data['start_date'])

            if serializer.data['end_date']:
                popup_data = popup_data.filter(created_at__date__lte=serializer.data['end_date'])
                popup_revenue = popup_revenue.filter(created_at__date__lte=serializer.data['end_date'])

            group_type = 'month'
            if serializer.data['group_type']:
                group_type = serializer.data['group_type']

            if group_type == 'hour':
                popup_data = popup_data.annotate(date=TruncTime('created_at'))
                popup_revenue = popup_revenue.annotate(date=TruncTime('created_at'))
            elif group_type == 'day':
                popup_data = popup_data.annotate(date=TruncDate('created_at'))
                popup_revenue = popup_revenue.annotate(date=TruncDate('created_at'))
            else:
                popup_data = popup_data.annotate(date=Trunc('created_at', group_type, output_field=DateField()))
                popup_revenue = popup_revenue.annotate(date=Trunc('created_at', group_type, output_field=DateField()))

            number_clicks = popup_data \
                .values('date') \
                .filter(type=TrackData.types['_click']) \
                .annotate(count=Sum('data_count')) \
                .values('date', 'count')

            number_purchase = popup_revenue \
                .values('date') \
                .annotate(count=Count('id')) \
                .values('date', 'count')

            return Response({
                'number_clicks': number_clicks,
                'number_purchases': number_purchase
            }, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class PopUpClicks(APIView):
    permission_classes = [IsTokenAuthenticated, ]
    serializer_class = PopupSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            popup_data = PopupData.objects.filter(
                type=TrackData.types['_click'],
                shop_id=serializer.data['shop_id']
            )

            if serializer.data['start_date']:
                popup_data = popup_data.filter(created_at__date__gte=serializer.data['start_date'])

            if serializer.data['end_date']:
                popup_data = popup_data.filter(created_at__date__lte=serializer.data['end_date'])

            group_type = 'month'
            if serializer.data['group_type']:
                group_type = serializer.data['group_type']

            if group_type == 'hour':
                popup_data = popup_data.annotate(date=TruncTime('created_at'))
            elif group_type == 'day':
                popup_data = popup_data.annotate(date=TruncDate('created_at'))
            else:
                popup_data = popup_data.annotate(date=Trunc('created_at', group_type, output_field=DateField()))

            data = popup_data.values('date') \
                .annotate(count=Sum('data_count')) \
                .values('date', 'count')

            return Response(data, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class PopUpPurchases(APIView):
    permission_classes = [IsTokenAuthenticated, ]
    serializer_class = PopupSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            popup_data = PopupRevenue.objects.filter(
                shop_id=serializer.data['shop_id']
            )

            if serializer.data['start_date']:
                popup_data = popup_data.filter(created_at__date__gte=serializer.data['start_date'])

            if serializer.data['end_date']:
                popup_data = popup_data.filter(created_at__date__lte=serializer.data['end_date'])

            group_type = 'month'
            if serializer.data['group_type']:
                group_type = serializer.data['group_type']

            if group_type == 'hour':
                popup_data = popup_data.annotate(date=TruncTime('created_at'))
            elif group_type == 'day':
                popup_data = popup_data.annotate(date=TruncDate('created_at'))
            else:
                popup_data = popup_data.annotate(date=Trunc('created_at', group_type, output_field=DateField()))

            data = popup_data.values('date') \
                .annotate(count=Count('id')) \
                .values('date', 'count')

            return Response(data, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class PopItems(APIView):
    permission_classes = [IsTokenAuthenticated, ]
    serializer_class = PopupSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():
            popup_data = PopupRevenue.objects.filter(
                articleID__isnull=False,
                shop_id=serializer.data['shop_id']
            )

            if serializer.data['start_date']:
                popup_data = popup_data.filter(created_at__date__gte=serializer.data['start_date'])

            if serializer.data['end_date']:
                popup_data = popup_data.filter(created_at__date__lte=serializer.data['end_date'])

            data = popup_data.values('articleID') \
                       .annotate(count=Sum('quantity')) \
                       .values('articleID', 'count') \
                       .order_by('-count')[:10]

            transaction_items = TransactionItems.objects.filter(
                transaction__shop_id=serializer.data['shop_id'],
                articleID__in=[item['articleID'] for item in data]
            )

            if serializer.data['start_date']:
                transaction_items = transaction_items.filter(transaction__created_at__date__gte=serializer.data['start_date'])

            if serializer.data['end_date']:
                transaction_items = transaction_items.filter(transaction__created_at__date__lte=serializer.data['end_date'])

            transaction_data = transaction_items.values('articleID') \
                .annotate(count=Sum('quantity')) \
                .values('articleID', 'count')

            response_data = []
            variants = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XS/S', 'S/M', 'M/L', 'L/XL', 'XL/XXL']
            transaction_items = {item['articleID']: item['count'] for item in transaction_data}

            for item in data:
                item_name = TransactionItems.objects.filter(
                    transaction__shop_id=serializer.data['shop_id'],
                    articleID=item['articleID']
                ).values('title').order_by('number').first()

                name = item_name['title'] if item_name else item['articleID']
                if name.split()[-1].upper() in variants:
                    name = name.rsplit(' ', 1)[0]

                count_purchases = 0
                if item['articleID'] in transaction_items:
                    count_purchases = transaction_items[item['articleID']]

                count_popup = item['count']
                if count_popup > count_purchases:
                    count_purchases = count_popup

                response = {
                    'name': name,
                    'count_popup': count_popup,
                    'count_purchases': count_purchases
                }
                response_data.append(response)

            return Response(response_data, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class PopUpRevenue(APIView):
    permission_classes = [IsTokenAuthenticated, ]
    serializer_class = PopupSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():
            popup_data = PopupRevenue.objects.filter(
                shop_id=serializer.data['shop_id']
            )

            if serializer.data['start_date']:
                popup_data = popup_data.filter(created_at__date__gte=serializer.data['start_date'])

            if serializer.data['end_date']:
                popup_data = popup_data.filter(created_at__date__lte=serializer.data['end_date'])

            group_type = 'month'
            if serializer.data['group_type']:
                group_type = serializer.data['group_type']

            if group_type == 'hour':
                popup_data = popup_data.annotate(date=TruncTime('created_at'))
            elif group_type == 'day':
                popup_data = popup_data.annotate(date=TruncDate('created_at'))
            else:
                popup_data = popup_data.annotate(date=Trunc('created_at', group_type, output_field=DateField()))

            data = popup_data.values('date') \
                .annotate(sumAmount=Sum('amount')) \
                .values('date', 'sumAmount')

            return Response(data, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)

class GetAnalyticDataByType(APIView):
    # permission_classes = [IsAuthenticated, ]
    serializer_class = AnalyticDataByTypeSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():
            # print(serializer.data)

            analytic = TrackData.objects.filter(
                type=serializer.data['type'],
                session__shop_id=serializer.data['shop_id']
            ).values('data').order_by('created_at')

            if analytic:
                return Response(analytic, status=status.HTTP_200_OK)
            else:
                return Response({}, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)


class GetSessionLastVoucher(APIView):
    # permission_classes = [IsAuthenticated, ]
    serializer_class = LastActionByTypeSerializer

    def get_serializer(self):
        return self.serializer_class()

    def post(self, request):
        serializer = self.serializer_class(data=request.data)
        if serializer.is_valid():

            analytic = TrackData.objects.filter(
                type=serializer.data['type'],
                session__shop_id=serializer.data['shop_id'],
                session__customer_id=serializer.data['customer_id'],
                session__session_id=serializer.data['session_id']
            ).values('data').order_by('-created_at').first()

            if analytic:
                return Response(analytic['data'], status=status.HTTP_200_OK)
            else:
                return Response({}, status=status.HTTP_200_OK)

        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)