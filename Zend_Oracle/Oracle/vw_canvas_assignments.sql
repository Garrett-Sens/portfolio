--
-- SOMCURRICULUM.VW_CANVAS_ASSIGNMENTS
-- @author Garrett Sens
--
-- This view combines data from multiple Canvas- and Tools-based tables related to Canvas assignment data.
--
create or replace view somcurriculum.vw_canvas_assignments as
select 
	ca.ca_id,
	ca.ca_name,
	ca.ca_description,
	ca.ca_due_at,
	ca.ca_published,
	ca.ca_assignment_group_id,
	cc.cc_id,
	cc.cc_name,
	(
		select replace( regexp_substr( cc.cc_name, '(ms ?\d\d\d\d)', 1, 1, 'i'), ' ', '' ) from dual -- extract string with "ms" (or "MS"; 'i' means 'case-insensitive search') then optional space then 4 digits. then remove any spaces.
	) as vca_class_year,
	ca.ca_date_inserted,
	ca.ca_inserter_id,
	ca.ca_date_updated,
	ca.ca_updater_id,
	caa.caa_id,
	caa.caa_lecture_name,
	caa.caa_lecture_location,
	trunc( caa.caa_lecture_date_start ) as vca_lecture_date,
	caa.caa_lecture_date_start,
	caa.caa_lecture_date_end,
	round( (caa.caa_lecture_date_end - caa.caa_lecture_date_start) * 24, 2 ) as vca_lecture_num_hours,
	round( (caa.caa_lecture_date_end - caa.caa_lecture_date_start) * 24 * 60, 2 ) as vca_lecture_num_minutes,
	cccs.cccs_id,
	cs.coursesectionid,
	cs.sectiontitle,
	nvl( ssum.numberstudents, 0 ) as numberstudents,
	cy.courseyearid,
	cy.academicyear,
	cy.academicyearblock,
	cy.coursetitle,
	cy.coursedescription,
	cy.schoolyear,
	cy.coursecredit,
	cy.coursedirector,
	nvl2( aud.namefirst, aud.namelast || ', ' || aud.namefirst, '' ) as vca_director_name_full,
	cy.coursecoordinator,
	nvl2( auc.namefirst, auc.namelast || ', ' || auc.namefirst, '' ) as vca_coordinator_name_full,
	cyg.vca_cyg_id_list,
	cyg.vca_cyg_text_list,
	caim_pm.vca_primary_caim_id,
	caim_pm.vca_primary_acim_id,
	caim_pm.vca_primary_acim_uid,
	caim_pm.vca_primary_acim_name,
	caim_im.vca_not_primary_caim_count,
	caim_im.vca_not_primary_caim_id_list,
	caim_im.vca_not_primary_acim_id_list,
	caim_im.vca_not_primary_acim_uid_list,
	caim_im.vca_not_primary_acim_name_list,
	caam.vca_caam_count,
	caam.vca_caam_id_list,
	caam.vca_acam_id_list,
	caam.vca_acam_uid_list,
	caam.vca_acam_name_list,
	caam.vca_acam_purpose_list,
	cart.vca_cart_count,
	cart.vca_cart_id_list,
	cart.vca_acrt_id_list,
	cart.vca_acrt_uid_list,
	cart.vca_acrt_name_list,
	cao.vca_cao_objective_id_list,
	cao.vca_cao_objective_text_list,
	cao.vca_cao_objective_count,
	cace.vca_cuep_id_list,
	cace.vca_cuep_code_list,
	cace.vca_cuep_description_list,
	cace.vca_cuep_id_count,
	cacs.vca_cusc_id_list,
	cacs.vca_cusc_name_list,
	cacs.vca_cusc_aamc_pcrs_id_list,
	cacs.vca_cusc_uu_id_list,
	cacs.vca_cusc_id_count,
	cafc.vca_faculty_unid_list,
	cafc.vca_faculty_name_list,
	cafc.vca_faculty_name_and_dept_list,
	cacf.vca_cacf_canvas_file_id_list,
	cacf.vca_cf_display_name_list,
	cacf.vca_cf_file_name_list,
	tnc.vca_concept_id_list,
	tnc.vca_concept_name_list
from canvas_assignments ca
left join canvas_assignment_additional caa
	on caa.caa_canvas_assignment_id = ca.ca_id
left join canvas_courses cc
	on cc.cc_id = ca.ca_canvas_course_id
left join canvas_course__course_section cccs
	on cccs.cccs_canvas_course_id = ca.ca_canvas_course_id
left join coursesection cs
	on cs.coursesectionid = cccs.cccs_course_section_id
left join courseyear cy
	on cy.courseyearid = cs.courseyearid
left join (
	select coursesectionid, count(studentkey) as numberstudents
	from somstudentaffairs.studentcoursesection
	group by coursesectionid
) ssum
	on cs.coursesectionid = ssum.coursesectionid
left join somadministration.applicationuser aud
	on aud.applicationuserid = cy.coursedirector
left join somadministration.applicationuser auc
	on auc.applicationuserid = cy.coursecoordinator
left join (
	select
		listagg(cyg.cyg_id, ';;;;') within group (order by cyg.cyg_order) as vca_cyg_id_list,
		listagg(cyg.cyg_text, ';;;;') within group (order by cyg.cyg_order) as vca_cyg_text_list,
		cyg.cyg_course_year_id
	from course_year_goals cyg
	group by cyg.cyg_course_year_id
) cyg
	on cyg.cyg_course_year_id = cy.courseyearid
left join (
	select
		caim.caim_id as vca_primary_caim_id,
		acim.acim_id as vca_primary_acim_id,
		acim.acim_uid as vca_primary_acim_uid,
		acim.acim_name as vca_primary_acim_name,
		caim.caim_canvas_assignment_id
	from canvas_assign_instruct_methods caim
	inner join aamc_ci_instructional_methods acim
		on caim.caim_instructional_method_id = acim.acim_id
	where caim.caim_is_primary_method = 1
) caim_pm
	on caim_pm.caim_canvas_assignment_id = ca.ca_id
left join (
	select
		count(caim.caim_id) as vca_not_primary_caim_count,
		listagg(caim.caim_id, ';;;;') within group (order by caim.caim_instructional_method_id) as vca_not_primary_caim_id_list,
		listagg(acim.acim_id, ';;;;') within group (order by acim.acim_id) as vca_not_primary_acim_id_list,
		listagg(acim.acim_uid, ';;;;') within group (order by acim.acim_id) as vca_not_primary_acim_uid_list,
		listagg(acim.acim_name, ';;;;') within group (order by acim.acim_id) as vca_not_primary_acim_name_list,
		caim.caim_canvas_assignment_id
	from canvas_assign_instruct_methods caim
	inner join aamc_ci_instructional_methods acim
		on caim.caim_instructional_method_id = acim.acim_id
	where caim.caim_is_primary_method != 1
	group by caim.caim_canvas_assignment_id
) caim_im
	on caim_im.caim_canvas_assignment_id = ca.ca_id
left join (
	select
		count(caam.caam_id) as vca_caam_count,
		listagg(caam.caam_id, ';;;;') within group (order by caam.caam_assessment_method_id) as vca_caam_id_list,
		listagg(acam.acam_id, ';;;;') within group (order by acam.acam_id) as vca_acam_id_list,
		listagg(acam.acam_uid, ';;;;') within group (order by acam.acam_id) as vca_acam_uid_list,
		listagg(acam.acam_name, ';;;;') within group (order by acam.acam_id) as vca_acam_name_list,
		listagg(acam.acam_purpose, ';;;;') within group (order by acam.acam_id) as vca_acam_purpose_list,
		caam.caam_canvas_assignment_id
	from canvas_assign_assess_methods caam
	inner join aamc_ci_assessment_methods acam
		on caam.caam_assessment_method_id = acam.acam_id
	group by caam.caam_canvas_assignment_id
) caam
	on caam.caam_canvas_assignment_id = ca.ca_id
left join (
	select
		count(cart.cart_id) as vca_cart_count,
		listagg(cart.cart_id, ';;;;') within group (order by cart.cart_resource_type_id) as vca_cart_id_list,
		listagg(acrt.acrt_id, ';;;;') within group (order by acrt.acrt_id) as vca_acrt_id_list,
		listagg(acrt.acrt_uid, ';;;;') within group (order by acrt.acrt_id) as vca_acrt_uid_list,
		listagg(acrt.acrt_name, ';;;;') within group (order by acrt.acrt_id) as vca_acrt_name_list,
		cart.cart_canvas_assignment_id
	from canvas_assign_resource_types cart
	inner join aamc_ci_resource_types acrt
		on cart.cart_resource_type_id = acrt.acrt_id
	group by cart.cart_canvas_assignment_id
) cart
	on cart.cart_canvas_assignment_id = ca.ca_id
left join (
	select
		listagg(cao.cao_id, ';;;;') within group (order by cao.cao_objective_order) as vca_cao_objective_id_list,
		listagg(cao.cao_objective_text, ';;;;') within group (order by cao.cao_objective_order) as vca_cao_objective_text_list,
		count(*) as vca_cao_objective_count,
		cao.cao_canvas_assignment_id
	from canvas_assign_objectives cao
	group by cao.cao_canvas_assignment_id
) cao
	on cao.cao_canvas_assignment_id = ca.ca_id
left join (
	select
		listagg(cuep.cuep_id, ';;;;') within group (order by cuep.cuep_code) as vca_cuep_id_list,
		listagg(cuep.cuep_code, ';;;;') within group (order by cuep.cuep_code) as vca_cuep_code_list,
		listagg(cuep.cuep_description, ';;;;') within group (order by cuep.cuep_description) as vca_cuep_description_list,
		count(*) as vca_cuep_id_count,
		cace.cace_canvas_assignment_id
	from canvas_assign__curr_epa cace
	left join curriculum_epas cuep on cace.cace_curr_epa_id = cuep.cuep_id
	group by cace.cace_canvas_assignment_id
) cace
	on cace.cace_canvas_assignment_id = ca.ca_id
left join (
	select
		listagg(cusc.cusc_id, ';;;;') within group (order by cusc.cusc_name) as vca_cusc_id_list,
		listagg(cusc.cusc_name, ';;;;') within group (order by cusc.cusc_name) as vca_cusc_name_list,
		listagg(cusc.cusc_aamc_pcrs_id, ';;;;') within group (order by cusc.cusc_aamc_pcrs_id) as vca_cusc_aamc_pcrs_id_list,
		listagg(cusc.cusc_uu_id, ';;;;') within group (order by cusc.cusc_uu_id) as vca_cusc_uu_id_list,
		count(*) as vca_cusc_id_count,
		cacs.cacs_canvas_assignment_id
	from canvas_assign__curr_subcomp cacs
	left join curriculum_subcompetencies cusc on cacs.cacs_curr_subcompetency_id = cusc.cusc_id
	group by cacs.cacs_canvas_assignment_id
) cacs
	on cacs.cacs_canvas_assignment_id = ca.ca_id
left join (
	select
		listagg(cafc.cafc_faculty_unid, ';;;;') within group (order by vfsu.preferredfullname) as vca_faculty_unid_list,
		listagg(vfsu.preferredfullname, ';;;;') within group (order by vfsu.preferredfullname) as vca_faculty_name_list,
		listagg(
			case when vfsu.preferredfullname is not null then 
				case when vfsu.vfs_affil_and_dept_epithet is not null then vfsu.preferredfullname || ' (' || vfsu.type || ', ' || vfsu.vfs_affil_and_dept_epithet || ')'
				else vfsu.preferredfullname || ' (' || vfsu.type || ')'
				end
			end,
			';;;;'
		) within group (order by vfsu.preferredfullname) as vca_faculty_name_and_dept_list,
		cafc.cafc_canvas_assignment_id
	from canvas_assignment__faculty cafc
	left join somcurriculum.vw_faculty_and_staff_unique vfsu
		on vfsu.unid = cafc.cafc_faculty_unid
		and cafc.cafc_faculty_unid is not null
	group by cafc.cafc_canvas_assignment_id
) cafc
	on cafc.cafc_canvas_assignment_id = ca.ca_id
left join (
	select
		listagg(cacf.cacf_canvas_file_id, ';;;;') within group (order by cf.cf_display_name) as vca_cacf_canvas_file_id_list,
		listagg(cf.cf_display_name, ';;;;') within group (order by cf.cf_display_name) as vca_cf_display_name_list,
		listagg(cf.cf_file_name, ';;;;') within group (order by cf.cf_display_name) as vca_cf_file_name_list,
		cacf.cacf_canvas_assignment_id
	from canvas_assignment__canvas_file cacf
	inner join canvas_files cf
		on cacf.cacf_canvas_file_id = cf.cf_id
	group by cacf.cacf_canvas_assignment_id
) cacf
	on cacf.cacf_canvas_assignment_id = ca.ca_id
left join (
	select
		listagg(tnc.concept_id, ';;;;') within group (order by tnc.concept) as vca_concept_id_list,
		listagg(tnc.concept, ';;;;') within group (order by tnc.concept) as vca_concept_name_list,
		tnc.event_id
	from somcurriculumbmi.top_n_concepts tnc
	group by tnc.event_id
) tnc
	on tnc.event_id = ca.ca_id;

alter view vw_canvas_assignments add constraint vca_pk primary key (ca_id) norely disable novalidate;

grant select on vw_canvas_assignments to somcurriculumweb;
