--
-- SOMCURRICULUM.CANVAS_ASSIGNMENTS
-- @author Garrett Sens
--
-- This table syncs with Canvas's "assignments" API endpoint and adds additional data useful to the School of Medicine.
--
create table somcurriculum.canvas_assignments (
    ca_id number(*,0)
        constraint ca_pk primary key,
    ca_name varchar2(255 char),
    ca_description clob,
    ca_due_at date,
    ca_published number(1,0)
        constraint ca_published_ck check( ca_published in (0,1) ),
    ca_assignment_group_id number(*,0),
        constraint ca_assignment_group_id_fk foreign key (ca_assignment_group_id) references somcurriculum.canvas_assignment_groups(cag_id),
    ca_canvas_course_id number(*,0)
        constraint ca_canvas_course_id_nk not null,
        constraint ca_canvas_course_id_fk foreign key (ca_canvas_course_id) references somcurriculum.canvas_courses(cc_id),
    ca_date_inserted date default current_date,
    ca_inserter_id varchar2(15 byte),
        constraint ca_inserter_id_fk foreign key (ca_inserter_id) references somadministration.applicationuser(applicationuserid),
    ca_date_updated date,
    ca_updater_id varchar2(15 byte),
        constraint ca_updater_id_fk foreign key (ca_updater_id) references somadministration.applicationuser(applicationuserid)
);

grant select, insert, update, delete on somcurriculum.canvas_assignments to somcurriculumweb;

create or replace trigger ca_trig
before delete or insert or update on somcurriculum.canvas_assignments
referencing old as oldvalues new as newvalues
for each row
begin
    if(updating) then
        :newvalues.ca_date_updated := current_date;
    end if;
end;
