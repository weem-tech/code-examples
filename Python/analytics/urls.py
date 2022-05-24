from django.conf.urls import url

from . import views

urlpatterns = [
    url(r'track-data/', views.TrackAnalyticData.as_view(), name='track-data'),
    url(r'get-last-action-by-type/', views.LastActionByType.as_view(), name='get-last-action-by-type'),
    url(r'get-last-session-popular/', views.LastSessionMostPopular.as_view(), name='get-last-session-popular'),
    url(r'recommendation-items/', views.RecommendationItems.as_view(), name='recommendation-items')
]
