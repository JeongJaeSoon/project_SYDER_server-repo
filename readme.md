# SYDER_Laravel Project
### Laravel Framework 6.18.3

<p align="center"><img src="https://res.cloudinary.com/dtfbvvkyp/image/upload/v1566331377/laravel-logolockup-cmyk-red.svg" width="400"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## About SYDER  
- 아파트, 대학 캠퍼스 등 '도로 외 구역' 에서 업무용 배달 이륜차 사고가 증가함에 따라 사고 발생률을 줄이기 위한 프로젝트
- 차량 자율주행 시스템을 접목하여 일정 구역 내에서 운용 가능한 운행 시스템

## Project progress
### [2020.02.05 ~ 2020.02.10]
- 기초 아이디어 제안 및 구성

<img src="https://user-images.githubusercontent.com/53788601/79687579-451bd500-8283-11ea-9ab8-b9ce6868ee8a.PNG" width="600px">
<br>

---

### [2020.02.11 ~ 2020.02.19]
- SYDER Project 진행방향 및 아이디어 기획
######
    - Mobile, Web, Server, H/W Systme Architecture 작성
    - 전체 스토리 진행 과정(Use case diagram) : 차량 호출 - 물건 배송 - 운행 중 - 물건 수령 - 운행 종료
    - Front, Back System configuration 작성
  
- Systems Architectrure

<img src="https://user-images.githubusercontent.com/53788601/79687239-d178c880-8280-11ea-8242-52f33f86044e.PNG" width="500px">
<br>

- Use case diagram

<img src="https://user-images.githubusercontent.com/53788601/79687241-d3db2280-8280-11ea-9d55-8a147b95d1bf.PNG" width="500px">
<br>

- System configuration

<img src="https://user-images.githubusercontent.com/53788601/79687240-d3428c00-8280-11ea-858d-c5397abb64b0.PNG" width="500px">
<br>
 
---

### [2020.02.21 ~ 2020.02.23]
##### DB Configuration
<img src="https://user-images.githubusercontent.com/53788601/79687735-574a4300-8284-11ea-89b9-9ec00d7de452.png" width="500px">
    
 ---
 
### [2020.02.24 ~ 2020.03.07]
##### Set Development Environment

<img src="https://user-images.githubusercontent.com/53788601/79687328-857a5380-8281-11ea-8f6d-05ea92429b95.jpg" width="275px">
<img src="https://user-images.githubusercontent.com/53788601/79687327-84492680-8281-11ea-856c-4e3122567589.png" width="300px">

- EC2 인스턴스 생성 및 Nginx 설정
######
    - Event-Driven 방식으로, 다중 요청 시 대처
    - 관리자, 발신자, 수신자, 차량 Client 의 요청 처리를 분산

- RDS 생성 및 Laravel 기초 Migration 작성
    
    
 ---
 
### [2020.03.08 ~ 2020.03.23]
##### Dev administartor page
    - Admin register, login, logout
    - Waypoint register, show, update, delete
    
 ---
 
### [2020.03.24 ~ 2020.04.09]
##### Apply Multi Authorization ([passport-multiauth](https://github.com/sfelix-martins/passport-multiauth))
    - Admin & User register, login, logout       
    - Apply request data validation(admin, user, wayponint)
    - Integration and Cleanup api route
    
 ---
 
### [2020.04.10 ~ 2020.05.03]
- Code Refactoring
- Summary of 'HTTP Response CODE'
######
    - 200 : OK, PATCH, DELETE
    - 201 : Created
    - 422 : Validation Error
    - 403 : Guard Error
    - 401 : Auth Error
    - 404 : Not Found

- Order Request API development
######
    - Order Index : Index list of registered orders
    - Order Check : Check orders in progress when the user app starts
    - Order Show : Delivery order information when the order request
    - Order Register : Register order information when the sender requests receiver consent
    - Admin, User AuthCheck : Check Client Auth when page loading
    - User Request : Receiver information response when sender input receiver phone number
 
 - Patch Client auth(need to add 'guard' request)
 ######
    - admin : Waypoint Register, Update, Delete, Order Index
    - user : Order Check, Show, Register, User Request
    - admin & user : Waypoint Index, Logout, AuthCheck
