// THE FILE WAS AUTOGENERATED USING PHP-CONVERTER. PLEASE DO NOT EDIT IT!
// THE FILE WAS AUTOGENERATED USING PHP-CONVERTER. PLEASE DO NOT EDIT IT!
// THE FILE WAS AUTOGENERATED USING PHP-CONVERTER. PLEASE DO NOT EDIT IT!

package gen

type FullName struct {
	FirstName string
	LastName  string
}

type Me struct {
	Request *UserCreate
}

type Profile struct {
	Name *FullName
	Age  int
}

type UserCreate struct {
	Id      string
	Profile *Profile
	Me      *Me
}
