from django.conf.urls import re_path
from django.urls import path
from mentor_connection import views
from rest_framework.urlpatterns import format_suffix_patterns

urlpatterns = [
	re_path(r'^mentors/?$', views.MentorList.as_view()),
	re_path(r'^mentors/(?P<pk>[0-9]+)/?$', views.MentorDetail.as_view()),
	re_path(r'^mentors/(?P<mentor_k>[0-9]+)/add/(?P<student_k>[0-9]+)/?$', views.MentorAddStudent.as_view()),
	re_path(r'^mentors/(?P<mentor_k>[0-9]+)/remove/(?P<student_k>[0-9]+)/?$', views.MentorRemoveStudent.as_view()),

	re_path(r'^students/?$', views.StudentList.as_view()),
	re_path(r'^students/(?P<pk>[0-9]+)/?$', views.StudentDetail.as_view(), name='student-detail'),

	re_path(r'^profile_photos/?$', views.ProfilePhotoUploadView.as_view()),
	re_path(r'^profile_photos/(?P<pk>[0-9]+)/?$', views.ProfilePhotoView.as_view()),

	re_path(r'^requests/?$', views.RequestList.as_view()),
	re_path(r'^requests/(?P<pk>[0-9]+)/?$', views.RequestDetail.as_view()),
	re_path(r'^requests/(?P<request_k>[0-9]+)/reject/?$', views.RequestReject.as_view()),
	re_path(r'^requests/(?P<request_k>[0-9]+)/accept/?$', views.RequestAccept.as_view()),

	re_path(r'^academicranks/?$', views.AcademicRankList.as_view()),
	re_path(r'^academicranks/(?P<pk>[0-9]+)/?$', views.AcademicRankDetail.as_view()),

	re_path(r'^credentials/?$', views.CredentialList.as_view()),
	re_path(r'^credentials/(?P<pk>[0-9]+)/?$', views.CredentialDetail.as_view()),

	re_path(r'^mentorshiptopics/?$', views.MentorshipTopicList.as_view()),
	re_path(r'^mentorshiptopics/(?P<pk>[0-9]+)/?$', views.MentorshipTopicDetail.as_view()),

	re_path(r'^practiceyears/?$', views.PracticeYearsList.as_view()),
	re_path(r'^practiceyears/(?P<pk>[0-9]+)/?$', views.PracticeYearsDetail.as_view()),

	re_path(r'^student_statuses/?$', views.StudentStatusList.as_view()),
	re_path(r'^student_statuses/(?P<pk>[0-9]+)/?$', views.StudentStatusDetail.as_view()),

	re_path(r'^departments/?$', views.DepartmentList.as_view()),
	re_path(r'^departments/(?P<pk>[0-9]+)/?$', views.DepartmentDetail.as_view()),

	re_path(r'^divisions/?$', views.DivisionList.as_view()),
	re_path(r'^divisions/(?P<pk>[0-9]+)/?$', views.DivisionDetail.as_view()),

	re_path(r'^sections/?$', views.SectionList.as_view()),
	re_path(r'^sections/(?P<pk>[0-9]+)/?$', views.SectionDetail.as_view()),

	re_path(r'^emails/?$', views.EmailList.as_view()),
	re_path(r'^emails/(?P<pk>[0-9]+)/?$', views.EmailDetail.as_view()),
]

urlpatterns = format_suffix_patterns(urlpatterns)
