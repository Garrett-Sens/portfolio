from django.db import models
from django.contrib.postgres.fields import ArrayField
from django.core.files.uploadedfile import InMemoryUploadedFile
from PIL import Image
from io import BytesIO
import sys
from django.utils.timezone import now

class AcademicRank(models.Model):
	name = models.CharField(max_length=2000, unique=True)
	is_active = models.BooleanField(default=True)

class Credential(models.Model):
	name = models.CharField(max_length=2000, unique=True)
	is_active = models.BooleanField(default=True)

class MentorshipTopic(models.Model):
	name = models.CharField(max_length=2000, unique=True)
	is_active = models.BooleanField(default=True)

class PracticeYears(models.Model):
	name = models.CharField(max_length=2000, unique=True)
	order = models.IntegerField(null=True)
	is_active = models.BooleanField(default=True)

class StudentStatus(models.Model):
	name = models.CharField(max_length=2000, unique=True)
	is_active = models.BooleanField(default=True)

class Department(models.Model):
	name = models.CharField(max_length=2000, unique=True)
	is_active = models.BooleanField(default=True)

class Division(models.Model):
	name = models.CharField(max_length=2000)
	is_active = models.BooleanField(default=True)
	department_id = models.ForeignKey(
		Department, on_delete=models.PROTECT, null=True)
	unique_together = [['name', 'department_id']]

class Section(models.Model):
	name = models.CharField(max_length=2000)
	is_active = models.BooleanField(default=True)
	division_id = models.ForeignKey(
		Division, on_delete=models.PROTECT, null=True)
	unique_together = [['name', 'division_id']]

class ProfilePhoto(models.Model):
	photo = models.ImageField(upload_to='media/profile_photos/')
	content_type = models.CharField(null=True, blank=True, max_length=100)

	def save(self, *args, **kwargs):
		PIL_image = Image.open(self.photo)
		buffer = BytesIO()
		x_pix, y_pix = PIL_image.size
		max_dim = 1000.
		if x_pix > max_dim or y_pix > max_dim:
			if x_pix > y_pix:
				new_size = int(max_dim), round(y_pix * max_dim / x_pix)
			else:
				new_size = round(x_pix * max_dim / y_pix), int(max_dim)
			PIL_image = PIL_image.resize(new_size, Image.ANTIALIAS)
		if PIL_image.mode in ("RGBA", "P"):
			PIL_image = PIL_image.convert('RGB')
		PIL_image.save(buffer, format='JPEG', quality=85, optimize=True)
		buffer.seek(0)
		self.photo = InMemoryUploadedFile(
			buffer,
			'ImageField',
			f"{''.join(self.photo.name.split('.')[:-1])}.jpg",
			'image/jpeg',
			sys.getsizeof(buffer),
			None)
		self.content_type = self.photo.file.content_type
		super().save(*args, **kwargs)

class Student(models.Model):
	name = models.CharField(max_length=2000)
	unid = models.CharField(max_length=20, unique=True)
	bio = models.CharField(max_length=2000, null=True)
	contact_email = models.CharField(max_length=2000)
	contact_phone = models.CharField(max_length=2000, null=True)
	professional_interests = ArrayField(
		models.CharField(blank=True, max_length=2000), null=True)
	mentorship_topics = models.ManyToManyField(MentorshipTopic, blank=True)
	student_status = models.ForeignKey(
		StudentStatus, on_delete=models.PROTECT, null=True)
	photo = models.ForeignKey(
		ProfilePhoto, on_delete=models.CASCADE, null=True)
	date_created = models.DateTimeField(default=now)
	do_contact = models.BooleanField(default=True)

class Mentor(models.Model):
	name = models.CharField(max_length=2000)
	unid = models.CharField(max_length=20, unique=True)
	credentials = models.ManyToManyField(Credential, blank=True)
	professional_title = models.CharField(max_length=2000, null=True)
	academic_rank = models.ForeignKey(
		AcademicRank, on_delete=models.PROTECT, null=True)
	bio = models.CharField(max_length=2000, null=True)
	department = models.ForeignKey(
		Department, on_delete=models.PROTECT, null=True)
	division = models.ForeignKey(Division, on_delete=models.PROTECT, null=True)
	section = models.ForeignKey(Section, on_delete=models.PROTECT, null=True)
	years_in_practice = models.ForeignKey(
		PracticeYears, on_delete=models.PROTECT, null=True)
	education_history = models.CharField(max_length=2000, null=True)
	mentee_capacity = models.IntegerField()
	contact_email = models.CharField(max_length=2000)
	office_location = models.CharField(max_length=2000, null=True)
	administrative_assistant_phone = models.CharField(
		max_length=2000, null=True)
	administrative_assistant_email = models.CharField(
		max_length=2000, null=True)
	preferred_contact = models.CharField(max_length=2000, null=True)
	professional_interests = ArrayField(
		models.CharField(
			blank=True,
			max_length=2000),
		blank=True,
		null=True)
	personal_interests = ArrayField(
		models.CharField(
			blank=True,
			max_length=2000),
		blank=True,
		null=True)
	mentorship_topics = models.ManyToManyField(MentorshipTopic, blank=True)
	photo = models.ForeignKey(
		ProfilePhoto,
		on_delete=models.CASCADE,
		null=True)
	students = models.ManyToManyField(Student, blank=True)
	twitter_handle = models.CharField(max_length=2000, null=True)
	date_created = models.DateTimeField(default=now)
	do_contact = models.BooleanField(default=True)

class Request(models.Model):
	mentor_id = models.ForeignKey(Mentor, on_delete=models.CASCADE)
	student_id = models.ForeignKey(Student, on_delete=models.CASCADE)
	created_at = models.DateTimeField(auto_now_add=True)
	updated_at = models.DateTimeField(auto_now=True)

	class AcceptanceStatusChoices(models.IntegerChoices):
		ACCEPTED = 1
		REJECTED = 2
		NO_RESPONSE = 3
	accepted_ind = models.IntegerField(
		choices=AcceptanceStatusChoices.choices,
		default=AcceptanceStatusChoices.NO_RESPONSE)

class Email(models.Model):
	to_email = models.CharField(max_length=2000)
	cc_email = models.CharField(max_length=2000, null=True)
	bcc_email = models.CharField(max_length=2000, null=True)
	from_email = models.CharField(max_length=2000)
	subject = models.CharField(max_length=2000)
	body = models.TextField()
	created_at = models.DateTimeField(auto_now_add=True)
	updated_at = models.DateTimeField(auto_now=True)
