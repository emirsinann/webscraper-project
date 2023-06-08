import shutil
import mysql.connector
import requests
from bs4 import BeautifulSoup


cnx = mysql.connector.connect(user='root', password='', host='localhost', database='scrape')

cursor = cnx.cursor(buffered=True)

url = "https://doktorbul.com"      # Doktorbul.com adresi

doc_list = []
res = []
exp_list = []
docexp_list = []
rev_list = []
doc_treats_list = []
img_list = []

def doc_name(link):  # Fetches the names of the doctors from the page and appends them to doc_list
    global doc_list
    global docexp_list
    doctors_section = link.find_all("h2")
    for doctor in doctors_section:
        doc_list.append(doctor.text.strip())


def nameLink_conv(): # Converst doc_list to url format and stores it in 'res'
    global res
    res = [sub.replace('.', '') for sub in doc_list]
    res = [sub.replace(' ', '-') for sub in res]
    res = [sub.replace('Ö', 'o') for sub in res]
    res = [sub.replace('ö', 'o') for sub in res]
    res = [sub.replace('Ç', 'c') for sub in res]
    res = [sub.replace('ç', 'c') for sub in res]
    res = [sub.replace('İ', 'i') for sub in res]
    res = [sub.replace('ı', 'i') for sub in res]
    res = [sub.replace('Ş', 's') for sub in res]
    res = [sub.replace('ş', 's') for sub in res]
    res = [sub.replace('Ü', 'u') for sub in res]
    res = [sub.replace('ü', 'u') for sub in res]
    res = [sub.replace('Ğ', 'g') for sub in res]
    res = [sub.replace('ğ', 'g') for sub in res]


def specializations(link): # Fetches the specializations of the doctors from the page and appends them to exp_list
    global exp_list
    
    box = link.find("div", class_="box_general")
    for small in box.find_all("small"):
        for onelink in small.find_all("a",href=True):
                exp_list.append(onelink.text.strip())
    erase_duplicates()

def doc_specialization(link): # Fetches the specializations of the doctors from the page and appends them to docexp_list
    global doc_list
    global docexp_list
    box = link.find("div", class_="box_general")
    for small in box.find_all("small"):
        temp = []
        for onelink in small.find_all("a",href=True):
            temp.append(onelink.text.strip())
        docexp_list.append(temp)
    for docexp in docexp_list: # Erasing the empty lists
        if docexp == []:
            docexp_list.remove([])



def erase_duplicates(): # Erasing the duplicates from lists
    global exp_list
    value_to_remove = '(5.00)'
    exp_list = [x for x in exp_list if x != value_to_remove]
    exp_list = list(dict.fromkeys(exp_list))

def doc_treats(link): # fetches the treatments of the doctor in page
    treats = link.find("ul", class_="bullets")
    list_treats = treats.find_all("li")
    temp =[] # [treat1, treat2, treat3]
    for treat in list_treats:
        temp.append(treat.text.strip())
    doc_treats_list.append(temp) # [[treat1, treat2, treat3], [treat1, treat2, treat3]]
        
        
def doc_reviews(link): # fetches the reviews of the doctor in page
    global rev_list
    reviews = link.find("div", class_="reviews-container") # take all reviews
    review_box = reviews.find_all("div", class_="review-box clearfix") # take all review boxes
    docrev_list = []
    #                            [[[name, rating, date, text], [name, rating, date, text]], [[name, rating, date, text], [name, rating, date, text]]]
    for review in review_box:
        temp = []
        rev_info = review.find("span", itemprop="author") # take name of the commenter
        rev_date = review.find("time", itemprop="datePublished") # take date of the comment
        rev_text = review.find("p", itemprop="reviewBody") # take text of the comment
        rev_rating = review.find("div", class_ ="rating") # take rating of the comment
        i = 0
        for rate in rev_rating.find_all("i", class_="icon_star voted"): # converting rate to int
            i+=1
        temp.append(rev_info.text.strip()) # name
        temp.append(i) # rating
        temp.append(rev_date.text.strip()) # date
        temp.append(rev_text.text.strip()) # text
        docrev_list.append(temp) # [[name, rating, date, text], [name, rating, date, text]]
        
    rev_list.append(docrev_list) # [[[name, rating, date, text], [name, rating, date, text]], [[name, rating, date, text], [name, rating, date, text]]]
    
def doc_img(link, i): # fetches the image of the doctor in page
    global img_list
    img = link.find("img", class_="img-fluid")
    full_url = img.attrs["src"]
    response = requests.get(full_url, stream=True)
    if response.status_code == 200:
        #with open('img/{}.jpg'.format(i), 'wb') as out_file:
            #shutil.copyfileobj(response.raw, out_file)
        img_list.append('img/{}.jpg'.format(i))
        del response


def sendPic_toDatabase(photo,i):
    try:
        sql_insert_blob_query = "UPDATE doctors SET doc_photo = %s WHERE doctor_id = %s"
        sql_insert_blob_values = (photo,i)

        # Convert data into tuple format
        result = cursor.execute(sql_insert_blob_query,sql_insert_blob_values)
        cnx.commit()
        print("Image inserted successfully as a varchar into doctors table", result)

    except mysql.connector.Error as error:
        print("Failed inserting BLOB data into MySQL table {}".format(error))
    
    


def cycle_through_pages(): # Surf through pages
    
    for i in range(1, 3): # Surf through pages 1-10
        
        website = f'{url}/doktorlar/sayfa-{i}' # Page link
        response = requests.get(website) # Get request
        soup = BeautifulSoup(response.text, "html.parser") # Parse
        doc_name(soup)
        specializations(soup)
        doc_specialization(soup)
        


def cylce_through_doctors():
    i = 1
    cycle_through_pages()
    nameLink_conv()
    for doc in res: # Loop through doctor personal pages
        url2 = f'{url}/{doc}' # Personal page link
        response = requests.get(url2) # Get request
        soup = BeautifulSoup(response.text, "html.parser") # Parse
        doc_treats(soup)
        doc_reviews(soup)
        doc_img(soup, i)
        i+=1
        print(doc_treats_list)
        print(rev_list)
        print(img_list)
        print(docexp_list)
        print(doc_list)
        print(exp_list)
        print("Database is being updated...")
    print("Database is updated.")

def send_doc_by_expertise():


    for exp in exp_list: # loop through expertise list
        
        cursor.execute("INSERT INTO expertise (expertise_name) VALUES (%s)", (exp,)) # Insert expertise
        cnx.commit()
        
    i = 0
    
    for doc in doc_list: # loop through doctor list
        cursor.execute("INSERT INTO doctors (doctor_name) VALUES (%s)", (doc,)) # Insert doctor
        cnx.commit()
        crs = cursor.lastrowid # Get last inserted doctor id
        temp = docexp_list[i] # Get doctor expertise list
        
        for exp in temp: # Loop through doctor expertise list
            cursor.execute("SELECT expertise_id FROM expertise WHERE expertise_name = %s", (exp,)) # Get expertise id
            crs2 = cursor.fetchone() # Get expertise id
            crs2 = int(crs2[0]) # Convert expertise id to int
            cursor.execute("INSERT INTO doc_expertise (doctor_id, expertise_id) VALUES (%s, %s)", (crs, crs2)) # Insert doctor expertise
            cnx.commit()
            
        i = i + 1
        
    cnx.commit()
        

def send_reviews():
    i = 0
    for doc in doc_list:
        cursor.execute("SELECT doctor_id FROM doctors WHERE doctor_name = %s", (doc,)) # Get doctor id
        crs = cursor.fetchone()
        crs = int(crs[0]) # Convert doctor id to int
        temp = rev_list[i]
        
        for rev in temp: # Loop through reviews
            cursor.execute("INSERT INTO ratings (doctor_id, patient_name, rating, comment, date) VALUES (%s, %s, %s, %s, %s)", (crs, rev[0], rev[1], rev[3], rev[2])) # Insert reviews into database
            cnx.commit()
            
        i = i + 1
        
    cnx.commit()

def send_treats(): # send treatments to database
    i = 0
    for doc in doc_list:
        cursor.execute("SELECT doctor_id FROM doctors WHERE doctor_name = %s", (doc,)) # Get doctor id
        crs = cursor.fetchone()
        crs = int(crs[0]) # Convert doctor id to int
        temp = doc_treats_list[i] # Get doctor treatments list
        
        for treat in temp: # Loop through treatments
            cursor.execute("INSERT INTO doc_diagnoses (doctor_id, diagnose_name) VALUES (%s, %s)", (crs, treat))
            cnx.commit()
            
        i = i + 1
        
    cnx.commit()


def send_docimg():
    i = 1
    for img in img_list:
        sendPic_toDatabase(img,i)
        print(img)
        i+=1


def send_database():
    cylce_through_doctors()
    send_doc_by_expertise()
    
cylce_through_doctors()
#send_docimg()
#send_database()
#send_reviews()
#send_treats()
