// Code generated by php-converter. DO NOT EDIT.
// Code generated by php-converter. DO NOT EDIT.
// Code generated by php-converter. DO NOT EDIT.

package gen

type Color int

const (
	RED   Color = 0
	BLUE  Color = 1
	WHITE Color = 2
)

type Role string

const (
	ADMIN  Role = "admin"
	EDITOR Role = "editor"
	READER Role = "reader"
)

type User struct {
	Color Color `json:"color"`
	User  int   `json:"user"`
	Role  Role  `json:"role"`
}
