from mentor_connection.models import (
	Mentor,
	Student,
	Request,
	AcademicRank,
	Credential,
	MentorshipTopic,
	PracticeYears,
	Department,
	Division,
	Section,
	ProfilePhoto,
	StudentStatus,
	Email
)
from mentor_connection.serializers import (
	MentorSerializer,
	StudentSerializer,
	RequestSerializer,
	AcademicRankSerializer,
	CredentialSerializer,
	MentorshipTopicSerializer,
	PracticeYearsSerializer,
	DepartmentSerializer,
	DivisionSerializer,
	SectionSerializer,
	ProfilePhotoUploadSerializer,
	StudentStatusSerializer,
	EmailSerializer
)
from django.http import HttpResponse
from rest_framework import generics, views, status
from rest_framework.response import Response
from rest_framework.parsers import FileUploadParser
from rest_framework.exceptions import ParseError
from django_filters import rest_framework as filters
from rest_framework.filters import OrderingFilter
from . import custom_filters
from .models import Mentor, Student


class AcademicRankList(generics.ListCreateAPIView):
	queryset = AcademicRank.objects.all()
	serializer_class = AcademicRankSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'is_active')


class AcademicRankDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = AcademicRank.objects.all()
	serializer_class = AcademicRankSerializer


class CredentialList(generics.ListCreateAPIView):
	queryset = Credential.objects.all()
	serializer_class = CredentialSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'is_active')

class CredentialDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Credential.objects.all()
	serializer_class = CredentialSerializer


class MentorshipTopicList(generics.ListCreateAPIView):
	queryset = MentorshipTopic.objects.all()
	serializer_class = MentorshipTopicSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'is_active')


class MentorshipTopicDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = MentorshipTopic.objects.all()
	serializer_class = MentorshipTopicSerializer


class PracticeYearsList(generics.ListCreateAPIView):
	queryset = PracticeYears.objects.all()
	serializer_class = PracticeYearsSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'is_active')


class PracticeYearsDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = PracticeYears.objects.all()
	serializer_class = PracticeYearsSerializer


class StudentStatusList(generics.ListCreateAPIView):
	queryset = StudentStatus.objects.all()
	serializer_class = StudentStatusSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'is_active')


class StudentStatusDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = StudentStatus.objects.all()
	serializer_class = StudentStatusSerializer


class DepartmentList(generics.ListCreateAPIView):
	queryset = Department.objects.all()
	serializer_class = DepartmentSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'is_active')


class DepartmentDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Department.objects.all()
	serializer_class = DepartmentSerializer


class DivisionList(generics.ListCreateAPIView):
	queryset = Division.objects.all()
	serializer_class = DivisionSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'department_id', 'is_active')


class DivisionDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Division.objects.all()
	serializer_class = DivisionSerializer


class SectionList(generics.ListCreateAPIView):
	queryset = Section.objects.all()
	serializer_class = SectionSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('name', 'division_id', 'is_active')


class SectionDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Section.objects.all()
	serializer_class = SectionSerializer


class MentorList(generics.ListCreateAPIView):
	queryset = Mentor.objects.all()
	serializer_class = MentorSerializer
	filter_backends = (filters.DjangoFilterBackend, OrderingFilter)
	filterset_class = custom_filters.MentorSearchFilter


class MentorAddStudent(views.APIView):
	def get(self, request, mentor_k, student_k, format=None):
		mentor = Mentor.objects.get(id=mentor_k)
		student = Student.objects.get(id=student_k)
		mentor.students.add(student)
		return Response(MentorSerializer(mentor).data)


class MentorRemoveStudent(views.APIView):
	def get(self, request, mentor_k, student_k, format=None):
		mentor = Mentor.objects.get(id=mentor_k)
		student = Student.objects.get(id=student_k)
		mentor.students.remove(student)
		return Response(MentorSerializer(mentor).data)


class MentorDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Mentor.objects.all()
	serializer_class = MentorSerializer


class StudentList(generics.ListCreateAPIView):
	queryset = Student.objects.all()
	serializer_class = StudentSerializer
	filter_backends = (filters.DjangoFilterBackend, OrderingFilter)
	filterset_class = custom_filters.StudentSearchFilter


class StudentDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Student.objects.all()
	serializer_class = StudentSerializer


class RequestList(generics.ListCreateAPIView):
	queryset = Request.objects.all()
	serializer_class = RequestSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('mentor_id', 'student_id', 'accepted_ind', 'id')


class RequestDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Request.objects.all()
	serializer_class = RequestSerializer


class RequestAccept(views.APIView):
	def get(self, request, request_k, format=None):
		request = Request.objects.get(id=request_k)
		request.accepted_ind = Request.AcceptanceStatusChoices.ACCEPTED
		mentor = Mentor.objects.get(id=request.mentor_id.id)
		student = Student.objects.get(id=request.student_id.id)
		mentor.students.add(student)
		request.save()
		return Response(StudentSerializer(student).data)


class RequestReject(views.APIView):
	def get(self, request, request_k, format=None):
		request = Request.objects.get(id=request_k)
		request.accepted_ind = Request.AcceptanceStatusChoices.REJECTED
		request.save()
		student = Student.objects.get(id=request.student_id.id)
		return Response(StudentSerializer(student).data)


class ProfilePhotoView(views.APIView):
	def get(self, request, pk):
		photo = ProfilePhoto.objects.get(id=pk).photo
		content_type = ProfilePhoto.objects.get(id=pk).content_type
		return HttpResponse(photo, content_type=content_type)


class ProfilePhotoUploadView(views.APIView):
	parser_class = (FileUploadParser,)
	def post(self, request, format=None):
		serializer = ProfilePhotoUploadSerializer(data=request.data, context={'request': request})
		if serializer.is_valid():
			photo_file = request.data.get
			serializer.save()
			serializer_data = serializer.data
			del serializer_data['photo']
			return Response(serializer_data, status=status.HTTP_201_CREATED)
		return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)

class EmailList(generics.ListCreateAPIView):
	queryset = Email.objects.all()
	serializer_class = EmailSerializer
	filter_backends = (filters.DjangoFilterBackend,)
	filter_fields = ('to_email','from_email')

class EmailDetail(generics.RetrieveUpdateDestroyAPIView):
	queryset = Email.objects.all()
	serializer_class = EmailSerializer
