from flask import Flask,render_template,request,session,redirect
from pymongo import MongoClient
import base64
import datetime
app = Flask(__name__)
#connect to  Mongo DB
#client=MongoClient('localhost',27017)
client=MongoClient('mongodb:####')
#create a database with name exk4817
db=client.exk4817

@app.route('/',methods=['POST','GET'])
def welcome():
    return render_template("login.html")

@app.route('/login',methods=['POST','GET'])
def login():
    user=request.form["user_name"]
    password=request.form["password"]
    user1=str(user)
    global users
    users=user1
    db_user = db.user.find({'username':user1,'password':password}).count()>0
    if (db_user):
        return render_template("index.html",user=users)
    else:
        return render_template("loginfailed.html")


@app.route('/upload',methods=['POST','GET'])
def upload():
    image=request.files['upload_picture']
    print image
    image_name=request.form['picture_name']
    print image_name
    comments=request.form['comments']
    print comments
    image_content=base64.b64encode(image.read())
    image_type=image.content_type
    image_size=image.tell()
    global users
    username=users
    image_details=db.pic.insert_one(
                                    {'username': username,
                                     'picname': image_name,
                                     'comments': comments,
                                     'content':image_content,
                                     'type':image_type,
                                     'size':image_size,
                                     'upload_time':datetime.datetime.now()
                                     }
                                    )
    return render_template("Success.html")


@app.route('/list',methods=['POST','GET'])
def list():
    global users
    pic_available=[]
    pic_name=[]
    cursor=db.pic.find({"username":users})
    for data in cursor:
        image=data['content']
        name=data['picname']
        pic=image.decode()
        pic_available.append(pic)
        pic_name.append(name)
    result=zip(pic_available,pic_name)
    print result
    return render_template("list.html ",response=result)

@app.route('/delete',methods=['POST','GET'])
def delete():
    image_delete=request.form['delete_picture']
    global users
    cursor=db.pic.delete_many({"username":users,"picname":image_delete})
    print(cursor.deleted_count)
    return render_template('Delete.html')

@app.route('/logout',methods=['POST','GET'])
def logout():
    return render_template("login.html")


if __name__ == '__main__':
    app.debug='true'
    app.run()















