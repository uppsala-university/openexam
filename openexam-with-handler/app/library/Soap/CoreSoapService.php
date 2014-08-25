<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    CoreSoapService.php
// Created: 2014-08-21 00:51:17
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core;

/**
 * Description of CoreSoapService
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
@WebService(serviceName = "OpenExamCoreService")
public class OpenExamCoreService {

    /**
     * Web service operation
     */
    @WebMethod(operationName = "createExam")
    public int createExam(@WebParam(name = "exam") Exam exam) {
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "editExam")
    public void editExam(@WebParam(name = "exam") Exam exam) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteExam")
    @Oneway
    public void deleteExam(@WebParam(name = "exam") int id) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getExams")
    public qnet.home.openexam.Exam[] getExams(@WebParam(name = "filter") Exam filter) {
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getContributors")
    public qnet.home.openexam.Contributor[] getContributors(@WebParam(name = "exam") int exam) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteContributor")
    @Oneway
    public void deleteContributor(@WebParam(name = "contributor") int contributor) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addContributor")
    public int addContributor(@WebParam(name = "contributor") Contributor contributor) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getAssistants")
    public qnet.home.openexam.Assistant[] getAssistants(@WebParam(name = "exam") int exam) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteAssistant")
    @Oneway
    public void deleteAssistant(@WebParam(name = "assistant") int assistant) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addAssistant")
    public int addAssistant(@WebParam(name = "assistant") Assistant assistant) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getDecoders")
    public qnet.home.openexam.Decoder[] getDecoders(@WebParam(name = "exam") int exam) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteDecoder")
    @Oneway
    public void deleteDecoder(@WebParam(name = "decoder") int decoder) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addDecoder")
    public int addDecoder(@WebParam(name = "decoder") Decoder decoder) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addTeacher")
    public int addTeacher(@WebParam(name = "teacher") Teacher teacher) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteTeacher")
    @Oneway
    public void deleteTeacher(@WebParam(name = "teacher") int teacher) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addAdmin")
    public int addAdmin(@WebParam(name = "admin") Admin admin) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteAdmin")
    @Oneway
    public void deleteAdmin(@WebParam(name = "admin") int admin) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addRoom")
    public int addRoom(@WebParam(name = "room") Room room) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "editRoom")
    @Oneway
    public void editRoom(@WebParam(name = "room") Room room) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteRoom")
    @Oneway
    public void deleteRoom(@WebParam(name = "room") int room) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addComputer")
    public int addComputer(@WebParam(name = "computer") Computer computer) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "editComputer")
    @Oneway
    public void editComputer(@WebParam(name = "computer") Computer computer) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteComputer")
    @Oneway
    public void deleteComputer(@WebParam(name = "computer") int computer) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getComputers")
    public qnet.home.openexam.Computer[] getComputers(@WebParam(name = "room") int room) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "aquireLock")
    @Oneway
    public void aquireLock(@WebParam(name = "exam") int exam, @WebParam(name = "computer") int computer) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "releaseLock")
    @Oneway
    public void releaseLock(@WebParam(name = "exam") int exam, @WebParam(name = "computer") int computer) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getLocks")
    public qnet.home.openexam.Lock[] getLocks(@WebParam(name = "exam") int exam) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addStudent")
    public int addStudent(@WebParam(name = "student") Student student) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addStudents")
    @Oneway
    public void addStudents(@WebParam(name = "exam") int exam, @WebParam(name = "students") qnet.home.openexam.Student[] students) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getStudents")
    public qnet.home.openexam.Student[] getStudents(@WebParam(name = "exam") int exam) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteStudent")
    @Oneway
    public void deleteStudent(@WebParam(name = "student") int student) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addQuestion")
    public int addQuestion(@WebParam(name = "question") Question question) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addQuestions")
    @Oneway
    public void addQuestions(@WebParam(name = "exam") int exam, @WebParam(name = "questions") qnet.home.openexam.Question[] questions) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "editQuestion")
    @Oneway
    public void editQuestion(@WebParam(name = "question") Question question) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteQuestion")
    @Oneway
    public void deleteQuestion(@WebParam(name = "question") int question) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getQuestions")
    public qnet.home.openexam.Question[] getQuestions(@WebParam(name = "exam") int exam, @WebParam(name = "topic") int topic) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "addTopic")
    public int addTopic(@WebParam(name = "topic") Topic topic) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "editTopic")
    @Oneway
    public void editTopic(@WebParam(name = "topic") Topic topic) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "deleteTopic")
    @Oneway
    public void deleteTopic(@WebParam(name = "topic") int topic) {
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getTopics")
    public qnet.home.openexam.Topic[] getTopics(@WebParam(name = "exam") int exam) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "setAnswer")
    public int setAnswer(@WebParam(name = "question") int question, @WebParam(name = "answer") Answer answer) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getAnswer")
    public Answer getAnswer(@WebParam(name = "question") int question, @WebParam(name = "student") int student) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getAnswers")
    public qnet.home.openexam.Answer[] getAnswers(@WebParam(name = "question") int question, @WebParam(name = "student") int student) {
        //TODO write your implementation code here:
        return null;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "setResult")
    public int setResult(@WebParam(name = "result") Result result) {
        //TODO write your implementation code here:
        return 0;
    }

    /**
     * Web service operation
     */
    @WebMethod(operationName = "getResult")
    public Result getResult(@WebParam(name = "answer") int answer) {
        //TODO write your implementation code here:
        return null;
    }
}
