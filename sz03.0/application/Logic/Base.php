<?php
/*
* 逻辑层基类
* 规范：
*      不能直接中断输出（exit, die等）
*	   执行结果失败直接抛出异常DobiException
*      层级关系model > logic > controllers, 上层不能调用下层（model不能调用logic）	
*/
class BaseLogic
{
    
}
