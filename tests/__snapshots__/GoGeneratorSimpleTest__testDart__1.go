type Category struct {
  id string
  title string
  rating int
  recipes []Recipe
};

type Recipe struct {
  id string
  imageUrl *string
  url *string
  isCooked bool
  weight int
};

type User struct {
  id string
  bestFriend *User
  friends []User
};
