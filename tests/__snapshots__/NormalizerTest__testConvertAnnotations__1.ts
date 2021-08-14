export type CloudNotify = {
  id: string;
  fcmToken: string;
};

export type UserCreate = {
  achievements: string[];
  name: string | null;
  age: number | string | number;
  isApproved: boolean | null;
  latitude: number;
  longitude: number;
  mixed: any;
};

